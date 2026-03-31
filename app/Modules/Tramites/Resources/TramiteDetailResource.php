<?php

namespace App\Modules\Tramites\Resources;

class TramiteDetailResource extends TramiteListItemResource
{
    public function toArray($request): array
    {
        return array_merge(parent::toArray($request), [
            'descripcion' => (string) ((is_array($this->resource) ? ($this->resource['descripcion'] ?? '') : '')),
        ]);
    }
}