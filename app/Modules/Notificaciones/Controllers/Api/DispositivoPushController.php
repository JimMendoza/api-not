<?php

namespace App\Modules\Notificaciones\Controllers\Api;

use App\Modules\Auth\Support\AuthenticatedAppUser;
use App\Modules\Notificaciones\Repositories\RealPushDeviceRepository;
use App\Modules\Notificaciones\Requests\InvalidateDispositivoPushRequest;
use App\Modules\Notificaciones\Requests\UpsertDispositivoPushRequest;
use App\Modules\Notificaciones\Resources\PushDeviceResource;
use App\Support\Http\Controllers\ApiController;

class DispositivoPushController extends ApiController
{
    protected RealPushDeviceRepository $realDevices;

    public function __construct(RealPushDeviceRepository $realDevices)
    {
        $this->realDevices = $realDevices;
    }

    public function upsert(UpsertDispositivoPushRequest $request)
    {
        /** @var AuthenticatedAppUser $usuario */
        $usuario = $request->user();

        $dispositivo = $this->realDevices->upsertForUser($usuario, $request->validated());

        return $this->ok([
            'mensaje' => 'Token push registrado correctamente.',
            'dispositivo' => (new PushDeviceResource($dispositivo))->resolve($request),
        ]);
    }

    public function invalidate(InvalidateDispositivoPushRequest $request)
    {
        /** @var AuthenticatedAppUser $usuario */
        $usuario = $request->user();

        $this->realDevices->invalidateForUser($usuario, (string) $request->validated()['deviceId']);

        return $this->ok([
            'mensaje' => 'Token push invalidado correctamente.',
        ]);
    }
}