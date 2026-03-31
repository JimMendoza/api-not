<?php

namespace App\Modules\Notificaciones\Resources;

use App\Support\Http\Resources\ApiJsonResource;

class PushDeviceResource extends ApiJsonResource
{
    public function toArray($request): array
    {
        $device = is_array($this->resource) ? $this->resource : [];

        return [
            'deviceId' => (string) ($device['deviceId'] ?? ''),
            'platform' => (string) ($device['platform'] ?? ''),
            'deviceName' => $device['deviceName'] ?? null,
            'appVersion' => $device['appVersion'] ?? null,
            'activo' => (bool) ($device['activo'] ?? false),
            'ultimoRegistroAt' => $device['ultimoRegistroAt'] ?? null,
        ];
    }
}