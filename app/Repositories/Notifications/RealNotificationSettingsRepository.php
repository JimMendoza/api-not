<?php

namespace App\Repositories\Notifications;

use App\Models\AppMobile\UsuarioNotificacionConfiguracion;
use App\Services\Auth\AuthenticatedAppUser;

class RealNotificationSettingsRepository
{
    public function settingsForUser(AuthenticatedAppUser $usuario): array
    {
        $defaults = UsuarioNotificacionConfiguracion::defaultSettings();
        $row = UsuarioNotificacionConfiguracion::query()
            ->where('usuario_id', $usuario->id)
            ->first();

        if (! $row) {
            return $defaults;
        }

        return $this->toPayload($row, $defaults);
    }

    public function updateForUser(AuthenticatedAppUser $usuario, array $settings): array
    {
        $payload = [
            'silenciar_fuera_de_horario' => (bool) $settings['silenciar_fuera_de_horario'],
            'hora_silencio_inicio' => (string) $settings['hora_silencio_inicio'],
            'hora_silencio_fin' => (string) $settings['hora_silencio_fin'],
            'zona_horaria' => UsuarioNotificacionConfiguracion::ZONA_HORARIA_FIJA,
            'mostrar_contador_no_leidas' => (bool) $settings['mostrar_contador_no_leidas'],
        ];

        $row = UsuarioNotificacionConfiguracion::query()->updateOrCreate(
            ['usuario_id' => $usuario->id],
            $payload
        );

        return $this->toPayload($row, UsuarioNotificacionConfiguracion::defaultSettings());
    }

    protected function toPayload(UsuarioNotificacionConfiguracion $row, array $defaults): array
    {
        return array_merge($defaults, [
            'silenciar_fuera_de_horario' => (bool) $row->silenciar_fuera_de_horario,
            'hora_silencio_inicio' => (string) $row->hora_silencio_inicio,
            'hora_silencio_fin' => (string) $row->hora_silencio_fin,
            'zona_horaria' => UsuarioNotificacionConfiguracion::ZONA_HORARIA_FIJA,
            'mostrar_contador_no_leidas' => (bool) $row->mostrar_contador_no_leidas,
        ]);
    }
}
