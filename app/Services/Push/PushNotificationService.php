<?php

namespace App\Services\Push;

use App\Jobs\SendPushNotificationJob;
use App\Models\AppMobile\UsuarioNotificacionConfiguracion;
use App\Repositories\Notifications\RealNotificationRepository;
use App\Repositories\Notifications\RealNotificationSettingsRepository;
use App\Repositories\Notifications\RealPushDeviceRepository;
use App\Services\Auth\AuthenticatedAppUser;
use Illuminate\Support\Facades\Log;
use Throwable;

class PushNotificationService
{
    protected PushSender $sender;
    protected RealNotificationRepository $notifications;
    protected RealNotificationSettingsRepository $settings;
    protected RealPushDeviceRepository $devices;

    public function __construct(
        PushSender $sender,
        RealNotificationRepository $notifications,
        RealNotificationSettingsRepository $settings,
        RealPushDeviceRepository $devices
    ) {
        $this->sender = $sender;
        $this->notifications = $notifications;
        $this->settings = $settings;
        $this->devices = $devices;
    }

    public function createInboxAndDispatch($usuario, $tramite, array $attributes = []): array
    {
        if (! $usuario instanceof AuthenticatedAppUser || ! is_array($tramite)) {
            $this->log('warning', 'push.invalid_real_context', [
                'usuarioValido' => $usuario instanceof AuthenticatedAppUser,
                'tramiteValido' => is_array($tramite),
            ]);

            return [
                'notificacion' => null,
                'push' => $this->result(false, false, 0, 0, 0, 'invalid_real_context'),
            ];
        }

        $tramiteId = isset($tramite['id']) ? (int) $tramite['id'] : 0;
        $context = $this->buildContext($usuario, $tramite, $attributes);

        if ($tramiteId <= 0 || ! $this->notifications->hasActiveFollowForUser($usuario, $tramiteId)) {
            $this->log('info', 'push.not_followed', $context);

            return [
                'notificacion' => null,
                'push' => $this->result($this->sender->isConfigured(), false, 0, 0, 0, 'not_followed'),
            ];
        }

        $row = $this->notifications->createForUser($usuario, $tramite, $attributes);
        $payload = $this->notifications->payloadFromRow($row);
        $context['notificationId'] = $payload['id'] ?? null;

        $this->log('info', 'push.inbox_created', $context);

        return [
            'notificacion' => $payload,
            'push' => $this->dispatchForUser($usuario, $payload, $context),
        ];
    }

    public function sendNowForUser(AuthenticatedAppUser $usuario, array $notificationPayload, array $context = []): array
    {
        $context = array_merge($this->buildContext($usuario, [
            'id' => $notificationPayload['tramiteId'] ?? null,
            'codigo' => $notificationPayload['codigoTramite'] ?? null,
        ], [
            'tipo' => $notificationPayload['tipo'] ?? null,
        ]), $context, [
            'notificationId' => $notificationPayload['id'] ?? null,
        ]);

        if (! $this->sender->isConfigured()) {
            $this->log('warning', 'push.provider_not_configured', $context);

            return $this->result(false, false, 0, 0, 0, 'provider_not_configured');
        }

        $decision = $this->shouldSendImmediatePush($this->settings->settingsForUser($usuario));

        if (! $decision['send']) {
            $this->log('info', 'push.suppressed', array_merge($context, [
                'reason' => $decision['reason'],
            ]));

            return $this->result(true, false, 0, 0, 0, $decision['reason']);
        }

        $dispositivos = $this->devices->activeDevicesForUser($usuario);

        if ($dispositivos->isEmpty()) {
            $this->log('info', 'push.no_active_devices', $context);

            return $this->result(true, false, 0, 0, 0, 'no_active_devices');
        }

        $sentDevices = 0;
        $invalidatedDevices = 0;
        $noLeidas = $this->notifications->unreadCountForUser($usuario);
        $message = $this->buildMessage($notificationPayload, $noLeidas);

        $this->log('info', 'push.dispatch_started', array_merge($context, [
            'attemptedDevices' => $dispositivos->count(),
            'noLeidas' => $noLeidas,
        ]));

        foreach ($dispositivos as $dispositivo) {
            try {
                $response = $this->sender->send($dispositivo, $message);
            } catch (Throwable $exception) {
                $this->log('error', 'push.device_send_exception', array_merge($context, [
                    'deviceId' => $dispositivo->device_id,
                    'exception' => $exception->getMessage(),
                ]));

                continue;
            }

            if (! empty($response['success'])) {
                $this->devices->touchLastPush((int) $dispositivo->id);
                $sentDevices++;
                continue;
            }

            if (! empty($response['invalidToken'])) {
                $this->devices->invalidateById((int) $dispositivo->id);
                $invalidatedDevices++;
            }
        }

        $reason = $sentDevices > 0 ? null : 'send_failed';
        $result = $this->result(true, false, $dispositivos->count(), $sentDevices, $invalidatedDevices, $reason);

        $this->log('info', 'push.dispatch_finished', array_merge($context, $result));

        return $result;
    }

    protected function dispatchForUser(AuthenticatedAppUser $usuario, array $notificationPayload, array $context): array
    {
        if (! $this->sender->isConfigured()) {
            $this->log('warning', 'push.provider_not_configured', $context);

            return $this->result(false, false, 0, 0, 0, 'provider_not_configured');
        }

        $decision = $this->shouldSendImmediatePush($this->settings->settingsForUser($usuario));

        if (! $decision['send']) {
            $this->log('info', 'push.suppressed', array_merge($context, [
                'reason' => $decision['reason'],
            ]));

            return $this->result(true, false, 0, 0, 0, $decision['reason']);
        }

        $attemptedDevices = $this->devices->activeDevicesForUser($usuario)->count();

        if ($attemptedDevices === 0) {
            $this->log('info', 'push.no_active_devices', $context);

            return $this->result(true, false, 0, 0, 0, 'no_active_devices');
        }

        try {
            dispatch(
                (new SendPushNotificationJob(
                    (int) $usuario->id,
                    (string) $usuario->empresaCodigo,
                    $notificationPayload,
                    $context
                ))->onQueue((string) config('mobile.push_queue', 'push'))
            );
        } catch (Throwable $exception) {
            $this->log('error', 'push.queue_dispatch_failed', array_merge($context, [
                'attemptedDevices' => $attemptedDevices,
                'exception' => $exception->getMessage(),
            ]));

            return $this->result(true, false, $attemptedDevices, 0, 0, 'queue_dispatch_failed');
        }

        $result = $this->result(true, true, $attemptedDevices, 0, 0, 'queued');

        $this->log('info', 'push.job_dispatched', array_merge($context, $result, [
            'queue' => config('mobile.push_queue', 'push'),
        ]));

        return $result;
    }

    protected function shouldSendImmediatePush(array $settings): array
    {
        if ($this->isInQuietHours($settings)) {
            return [
                'send' => false,
                'reason' => 'silenciar_fuera_de_horario',
            ];
        }

        return [
            'send' => true,
            'reason' => null,
        ];
    }

    protected function isInQuietHours(array $settings): bool
    {
        if (empty($settings['silenciar_fuera_de_horario'])) {
            return false;
        }

        $inicio = $this->minutesFromClock($settings['hora_silencio_inicio'] ?? null);
        $fin = $this->minutesFromClock($settings['hora_silencio_fin'] ?? null);

        if ($inicio === null || $fin === null || $inicio === $fin) {
            return false;
        }

        $ahora = now(UsuarioNotificacionConfiguracion::ZONA_HORARIA_FIJA);
        $actual = ((int) $ahora->format('H') * 60) + (int) $ahora->format('i');

        if ($inicio < $fin) {
            return $actual >= $inicio && $actual < $fin;
        }

        return $actual >= $inicio || $actual < $fin;
    }

    protected function minutesFromClock($value): ?int
    {
        if (! is_string($value) || ! preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/', $value)) {
            return null;
        }

        [$hour, $minute] = explode(':', $value);

        return ((int) $hour * 60) + (int) $minute;
    }

    protected function buildMessage(array $notificationPayload, int $noLeidas): array
    {
        return [
            'notification' => [
                'title' => (string) $notificationPayload['titulo'],
                'body' => (string) $notificationPayload['mensaje'],
            ],
            'data' => [
                'notificationId' => (string) $notificationPayload['id'],
                'tramiteId' => (string) $notificationPayload['tramiteId'],
                'codigoTramite' => (string) ($notificationPayload['codigoTramite'] ?? ''),
                'tipo' => (string) $notificationPayload['tipo'],
                'targetScreen' => 'notificaciones',
                'noLeidas' => (string) $noLeidas,
            ],
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'channel_id' => 'notificaciones_generales',
                    'notification_count' => $noLeidas,
                ],
            ],
            'apns' => [
                'headers' => [
                    'apns-priority' => '10',
                ],
                'payload' => [
                    'aps' => [
                        'sound' => 'default',
                    ],
                ],
            ],
        ];
    }

    protected function result(
        bool $configured,
        bool $queued,
        int $attemptedDevices,
        int $sentDevices,
        int $invalidatedDevices,
        ?string $reason
    ): array {
        return [
            'provider' => $this->sender->provider(),
            'configured' => $configured,
            'queued' => $queued,
            'attemptedDevices' => $attemptedDevices,
            'sentDevices' => $sentDevices,
            'invalidatedDevices' => $invalidatedDevices,
            'reason' => $reason,
        ];
    }

    protected function buildContext(AuthenticatedAppUser $usuario, array $tramite, array $attributes = []): array
    {
        return [
            'usuarioId' => $usuario->id,
            'empresaCodigo' => $usuario->empresaCodigo,
            'tramiteId' => isset($tramite['id']) ? (int) $tramite['id'] : null,
            'codigoTramite' => $tramite['codigo'] ?? null,
            'tipo' => $attributes['tipo'] ?? null,
            'evento' => $attributes['evento'] ?? null,
        ];
    }

    protected function log(string $level, string $message, array $context = []): void
    {
        Log::channel((string) config('mobile.log_channel', config('logging.default')))
            ->{$level}($message, $context);
    }
}
