<?php

namespace App\Repositories\Notifications;

use App\Models\AppMobile\UsuarioDispositivo;
use App\Services\Auth\AuthenticatedAppUser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RealPushDeviceRepository
{
    public function upsertForUser(AuthenticatedAppUser $usuario, array $data): array
    {
        $device = UsuarioDispositivo::query()->updateOrCreate(
            ['device_id' => (string) $data['deviceId']],
            [
                'usuario_id' => $usuario->id,
                'push_token' => (string) $data['pushToken'],
                'platform' => (string) $data['platform'],
                'device_name' => $data['deviceName'] ?? null,
                'app_version' => $data['appVersion'] ?? null,
                'activo' => true,
                'ultimo_registro_at' => now(),
                'invalidado_at' => null,
            ]
        );

        return $this->toPayload($device->fresh());
    }

    public function invalidateForUser(AuthenticatedAppUser $usuario, string $deviceId): void
    {
        UsuarioDispositivo::query()
            ->where('usuario_id', $usuario->id)
            ->where('device_id', $deviceId)
            ->update([
                'activo' => false,
                'invalidado_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function activeDevicesForUser(AuthenticatedAppUser $usuario): Collection
    {
        return UsuarioDispositivo::query()
            ->where('usuario_id', $usuario->id)
            ->active()
            ->get();
    }

    public function touchLastPush(int $deviceId): void
    {
        UsuarioDispositivo::query()
            ->where('id', $deviceId)
            ->update([
                'ultimo_push_at' => now(),
            ]);
    }

    public function invalidateById(int $deviceId): void
    {
        UsuarioDispositivo::query()
            ->where('id', $deviceId)
            ->update([
                'activo' => false,
                'invalidado_at' => now(),
                'updated_at' => now(),
            ]);
    }

    protected function toPayload(UsuarioDispositivo $dispositivo): array
    {
        return [
            'deviceId' => (string) $dispositivo->device_id,
            'platform' => (string) $dispositivo->platform,
            'deviceName' => $dispositivo->device_name,
            'appVersion' => $dispositivo->app_version,
            'activo' => (bool) $dispositivo->activo,
            'ultimoRegistroAt' => $dispositivo->ultimo_registro_at
                ? Carbon::parse($dispositivo->ultimo_registro_at)->format('Y-m-d H:i:s')
                : null,
        ];
    }
}