<?php

namespace App\Modules\Notificaciones\Resources;

use App\Support\Http\Resources\ApiJsonResource;

class NotificationSettingsResource extends ApiJsonResource
{
    public function toArray($request): array
    {
        $settings = is_array($this->resource) ? $this->resource : [];

        return [
            'silenciar_fuera_de_horario' => (bool) ($settings['silenciar_fuera_de_horario'] ?? false),
            'hora_silencio_inicio' => (string) ($settings['hora_silencio_inicio'] ?? ''),
            'hora_silencio_fin' => (string) ($settings['hora_silencio_fin'] ?? ''),
            'zona_horaria' => (string) ($settings['zona_horaria'] ?? ''),
            'mostrar_contador_no_leidas' => (bool) ($settings['mostrar_contador_no_leidas'] ?? false),
        ];
    }
}