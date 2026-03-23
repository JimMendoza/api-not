<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Requests\Api\App\InvalidateDispositivoPushRequest;
use App\Http\Requests\Api\App\UpsertDispositivoPushRequest;
use App\Models\UsuarioApp;
use App\Models\UsuarioAppDispositivo;

class DispositivoPushController extends ApiController
{
    public function upsert(UpsertDispositivoPushRequest $request)
    {
        /** @var UsuarioApp $usuario */
        $usuario = $request->user();

        $data = $request->validated();

        $dispositivo = UsuarioAppDispositivo::query()->updateOrCreate(
            [
                'device_id' => $data['deviceId'],
            ],
            [
                'usuario_app_id' => $usuario->id,
                'push_token' => $data['pushToken'],
                'platform' => $data['platform'],
                'device_name' => $data['deviceName'] ?? null,
                'app_version' => $data['appVersion'] ?? null,
                'activo' => true,
                'ultimo_registro_at' => now(),
                'invalidado_at' => null,
            ]
        );

        return $this->ok([
            'mensaje' => 'Token push registrado correctamente.',
            'dispositivo' => $this->toPayload($dispositivo->fresh()),
        ]);
    }

    public function invalidate(InvalidateDispositivoPushRequest $request)
    {
        /** @var UsuarioApp $usuario */
        $usuario = $request->user();

        UsuarioAppDispositivo::query()
            ->where('usuario_app_id', $usuario->id)
            ->where('device_id', $request->validated()['deviceId'])
            ->update([
                'activo' => false,
                'invalidado_at' => now(),
            ]);

        return $this->ok([
            'mensaje' => 'Token push invalidado correctamente.',
        ]);
    }

    protected function toPayload(UsuarioAppDispositivo $dispositivo)
    {
        return [
            'deviceId' => $dispositivo->device_id,
            'platform' => $dispositivo->platform,
            'deviceName' => $dispositivo->device_name,
            'appVersion' => $dispositivo->app_version,
            'activo' => (bool) $dispositivo->activo,
            'ultimoRegistroAt' => optional($dispositivo->ultimo_registro_at)->format('Y-m-d H:i:s'),
        ];
    }
}
