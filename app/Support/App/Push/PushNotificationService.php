<?php

namespace App\Support\App\Push;

use App\Models\Notificacion;
use App\Models\Tramite;
use App\Models\UsuarioApp;
use App\Models\UsuarioAppDispositivo;
use App\Models\UsuarioAppNotificacionConfiguracion;

class PushNotificationService
{
    protected $sender;

    public function __construct(PushSender $sender)
    {
        $this->sender = $sender;
    }

    public function createInboxAndSend(UsuarioApp $usuario, Tramite $tramite, array $attributes = [])
    {
        $notificacion = Notificacion::query()->create([
            'tramite_id' => $tramite->id,
            'usuario_app_id' => $usuario->id,
            'titulo' => $attributes['titulo'] ?? 'Nueva notificacion',
            'mensaje' => $attributes['mensaje'] ?? 'Push real generado desde backend.',
            'tipo' => $attributes['tipo'] ?? 'evento',
            'leida' => false,
            'fecha_hora' => now(),
        ]);

        $notificacion->loadMissing('tramite', 'usuarioApp.configuracionNotificaciones');

        return [
            'notificacion' => $notificacion,
            'push' => $this->sendForNotificacion($notificacion),
        ];
    }

    public function sendForNotificacion(Notificacion $notificacion)
    {
        $notificacion->loadMissing('tramite', 'usuarioApp.configuracionNotificaciones');

        /** @var UsuarioApp|null $usuario */
        $usuario = $notificacion->usuarioApp;

        if (! $usuario) {
            return $this->result(false, 0, 0, 0, 'usuario_no_encontrado');
        }

        if (! $this->sender->isConfigured()) {
            return $this->result(false, 0, 0, 0, 'provider_not_configured');
        }

        $decision = $this->shouldSendImmediatePush($usuario);

        if (! $decision['send']) {
            return $this->result(true, 0, 0, 0, $decision['reason']);
        }

        $dispositivos = UsuarioAppDispositivo::query()
            ->where('usuario_app_id', $usuario->id)
            ->active()
            ->get();

        if ($dispositivos->isEmpty()) {
            return $this->result(true, 0, 0, 0, 'no_active_devices');
        }

        $sentDevices = 0;
        $invalidatedDevices = 0;
        $noLeidas = Notificacion::query()
            ->visibleForUsuario($usuario)
            ->where('leida', false)
            ->count();
        $message = $this->buildMessage($notificacion, $noLeidas);

        foreach ($dispositivos as $dispositivo) {
            $response = $this->sender->send($dispositivo, $message);

            if (! empty($response['success'])) {
                $dispositivo->forceFill([
                    'ultimo_push_at' => now(),
                ])->save();
                $sentDevices++;
                continue;
            }

            if (! empty($response['invalidToken'])) {
                $dispositivo->forceFill([
                    'activo' => false,
                    'invalidado_at' => now(),
                ])->save();
                $invalidatedDevices++;
            }
        }

        $reason = $sentDevices > 0 ? null : 'send_failed';

        return $this->result(true, $dispositivos->count(), $sentDevices, $invalidatedDevices, $reason);
    }

    protected function shouldSendImmediatePush(UsuarioApp $usuario)
    {
        $settings = $this->settingsForUsuario($usuario);

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

    protected function settingsForUsuario(UsuarioApp $usuario)
    {
        $configuracion = $usuario->configuracionNotificaciones;
        $defaults = UsuarioAppNotificacionConfiguracion::defaultSettings();

        if (! $configuracion) {
            return $defaults;
        }

        $settings = array_merge(
            $defaults,
            $configuracion->only(UsuarioAppNotificacionConfiguracion::settingsKeys())
        );

        $settings['zona_horaria'] = UsuarioAppNotificacionConfiguracion::ZONA_HORARIA_FIJA;

        return $settings;
    }

    protected function isInQuietHours(array $settings)
    {
        if (empty($settings['silenciar_fuera_de_horario'])) {
            return false;
        }

        $inicio = $this->minutesFromClock($settings['hora_silencio_inicio'] ?? null);
        $fin = $this->minutesFromClock($settings['hora_silencio_fin'] ?? null);

        if ($inicio === null || $fin === null || $inicio === $fin) {
            return false;
        }

        $ahora = now(UsuarioAppNotificacionConfiguracion::ZONA_HORARIA_FIJA);
        $actual = ((int) $ahora->format('H') * 60) + (int) $ahora->format('i');

        if ($inicio < $fin) {
            return $actual >= $inicio && $actual < $fin;
        }

        return $actual >= $inicio || $actual < $fin;
    }

    protected function minutesFromClock($value)
    {
        if (! is_string($value) || ! preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/', $value)) {
            return null;
        }

        [$hour, $minute] = explode(':', $value);

        return ((int) $hour * 60) + (int) $minute;
    }

    protected function buildMessage(Notificacion $notificacion, $noLeidas)
    {
        return [
            'notification' => [
                'title' => (string) $notificacion->titulo,
                'body' => (string) $notificacion->mensaje,
            ],
            'data' => [
                'notificationId' => (string) $notificacion->id,
                'tramiteId' => (string) $notificacion->tramite_id,
                'codigoTramite' => (string) optional($notificacion->tramite)->codigo,
                'tipo' => (string) $notificacion->tipo,
                'targetScreen' => 'notificaciones',
                'noLeidas' => (string) $noLeidas,
            ],
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'channel_id' => 'notificaciones_generales',
                    'notification_count' => (int) $noLeidas,
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

    protected function result($configured, $attemptedDevices, $sentDevices, $invalidatedDevices, $reason)
    {
        return [
            'provider' => $this->sender->provider(),
            'configured' => (bool) $configured,
            'attemptedDevices' => (int) $attemptedDevices,
            'sentDevices' => (int) $sentDevices,
            'invalidatedDevices' => (int) $invalidatedDevices,
            'reason' => $reason,
        ];
    }
}
