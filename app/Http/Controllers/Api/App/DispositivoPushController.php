<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Requests\Api\App\InvalidateDispositivoPushRequest;
use App\Http\Requests\Api\App\UpsertDispositivoPushRequest;
use App\Repositories\Notifications\RealPushDeviceRepository;
use App\Services\Auth\AuthenticatedAppUser;

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
            'dispositivo' => $dispositivo,
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
