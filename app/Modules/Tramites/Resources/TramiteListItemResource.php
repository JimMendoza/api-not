<?php

namespace App\Modules\Tramites\Resources;

use App\Support\Http\Resources\ApiJsonResource;

class TramiteListItemResource extends ApiJsonResource
{
    public function toArray($request): array
    {
        $tramite = is_array($this->resource) ? $this->resource : [];
        $siguiendo = (bool) ($tramite['siguiendo'] ?? false);

        return [
            'id' => (int) ($tramite['id'] ?? 0),
            'codigo' => (string) ($tramite['codigo'] ?? ''),
            'titulo' => (string) ($tramite['titulo'] ?? ''),
            'fecha' => (string) ($tramite['fecha'] ?? ''),
            'estadoActual' => (string) ($tramite['estadoActual'] ?? ''),
            'siguiendo' => $siguiendo,
            'notificacionesNoLeidas' => $siguiendo
                ? (int) ($tramite['notificacionesNoLeidas'] ?? 0)
                : 0,
        ];
    }
}