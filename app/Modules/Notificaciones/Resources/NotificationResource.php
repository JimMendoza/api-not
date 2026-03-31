<?php

namespace App\Modules\Notificaciones\Resources;

use App\Support\Http\Resources\ApiJsonResource;

class NotificationResource extends ApiJsonResource
{
    public function toArray($request): array
    {
        $notification = is_array($this->resource) ? $this->resource : [];

        return [
            'id' => (int) ($notification['id'] ?? 0),
            'tramiteId' => (int) ($notification['tramiteId'] ?? 0),
            'codigoTramite' => (string) ($notification['codigoTramite'] ?? ''),
            'titulo' => (string) ($notification['titulo'] ?? ''),
            'mensaje' => (string) ($notification['mensaje'] ?? ''),
            'tipo' => (string) ($notification['tipo'] ?? ''),
            'leida' => (bool) ($notification['leida'] ?? false),
            'fechaHora' => $notification['fechaHora'] ?? null,
        ];
    }
}