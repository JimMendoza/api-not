<?php

namespace App\Services\Push;

use App\Models\AppMobile\UsuarioNotificacionConfiguracion;
use App\Repositories\Notifications\RealNotificationRepository;
use App\Repositories\Notifications\RealNotificationSettingsRepository;
use App\Repositories\Notifications\RealPushDeviceRepository;
use App\Services\Auth\AuthenticatedAppUser;

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

    public function createInboxAndSend($usuario, $tramite, array $attributes = []): array
    {
        if (! $usuario instanceof AuthenticatedAppUser || ! is_array($tramite)) {
            return [
                'notificacion' => null,
                'push' => $this->result(false, 0, 0, 0, 'invalid_real_context'),
            ];
        }

        $tramiteId = isset($tramite['id']) ? (int) $tramite['id'] : 0;

        if ($tramiteId <= 0 || ! $this->notifications->hasActiveFollowForUser($usuario, $tramiteId)) {
            return [
                'notificacion' => null,
                'push' => $this->result($this->sender->isConfigured(), 0, 0, 0, 'not_followed'),
            ];
        }

        $row = $this->notifications->createForUser($usuario, $tramite, $attributes);
        $payload = $this->notifications->payloadFromRow($row);

        return [
            'notificacion' => $payload,
            'push' => $this->sendForUser($usuario, $payload),
        ];
    }

    protected function sendForUser(AuthenticatedAppUser $usuario, array $notificationPayload): array
    {
        if (! $this->sender->isConfigured()) {
            return $this->result(false, 0, 0, 0, 'provider_not_configured');
        }

        $decision = $this->shouldSendImmediatePush($this->settings->settingsForUser($usuario));

        if (! $decision['send']) {
            return $this->result(true, 0, 0, 0, $decision['reason']);
        }

        $dispositivos = $this->devices->activeDevicesForUser($usuario);

        if ($dispositivos->isEmpty()) {
            return $this->result(true, 0, 0, 0, 'no_active_devices');
        }

        $sentDevices = 0;
        $invalidatedDevices = 0;
        $noLeidas = $this->notifications->unreadCountForUser($usuario);
        $message = $this->buildMessage($notificationPayload, $noLeidas);

        foreach ($dispositivos as $dispositivo) {
            $response = $this->sender->send($dispositivo, $message);

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

        return $this->result(true, $dispositivos->count(), $sentDevices, $invalidatedDevices, $reason);
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
        int $attemptedDevices,
        int $sentDevices,
        int $invalidatedDevices,
        ?string $reason
    ): array {
        return [
            'provider' => $this->sender->provider(),
            'configured' => $configured,
            'attemptedDevices' => $attemptedDevices,
            'sentDevices' => $sentDevices,
            'invalidatedDevices' => $invalidatedDevices,
            'reason' => $reason,
        ];
    }
}
