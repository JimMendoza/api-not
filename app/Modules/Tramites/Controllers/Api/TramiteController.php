<?php

namespace App\Modules\Tramites\Controllers\Api;

use App\Modules\Auth\Support\AuthenticatedAppUser;
use App\Modules\Tramites\Repositories\RealTramiteRepository;
use App\Modules\Tramites\Resources\TramiteDetailResource;
use App\Modules\Tramites\Resources\TramiteListItemResource;
use App\Support\Http\Controllers\ApiController;
use Illuminate\Http\Request;

class TramiteController extends ApiController
{
    protected RealTramiteRepository $realTramiteRepository;

    public function __construct(RealTramiteRepository $realTramiteRepository)
    {
        $this->realTramiteRepository = $realTramiteRepository;
    }

    public function index(Request $request)
    {
        /** @var AuthenticatedAppUser $usuario */
        $usuario = $request->user();

        $tramites = $this->realTramiteRepository->listVisibleForUser($usuario);

        return $this->ok(array_values(
            TramiteListItemResource::collection(collect($tramites))->resolve($request)
        ));
    }

    public function show(Request $request, $id)
    {
        /** @var AuthenticatedAppUser $usuario */
        $usuario = $request->user();

        $tramite = $this->realTramiteRepository->findVisibleForUser($usuario, (int) $id);

        if (! $tramite) {
            return $this->error('Trámite no encontrado.', 404);
        }

        return $this->ok((new TramiteDetailResource($tramite))->resolve($request));
    }

    public function hojaRuta(Request $request, $id)
    {
        /** @var AuthenticatedAppUser $usuario */
        $usuario = $request->user();

        $tramite = $this->realTramiteRepository->findVisibleForUser($usuario, (int) $id);

        if (! $tramite) {
            return $this->error('Trámite no encontrado.', 404);
        }

        return $this->error('Hoja de ruta no disponible mientras no exista una fuente real de movimientos.', 501);
    }

    public function seguir(Request $request, $id)
    {
        /** @var AuthenticatedAppUser $usuario */
        $usuario = $request->user();

        $tramite = $this->realTramiteRepository->findVisibleForUser($usuario, (int) $id);

        if (! $tramite) {
            return $this->error('Trámite no encontrado.', 404);
        }

        $this->realTramiteRepository->markFollowed($usuario, (int) $tramite['id']);

        return $this->ok([
            'mensaje' => 'Trámite marcado para seguimiento.',
        ]);
    }

    public function dejarSeguir(Request $request, $id)
    {
        /** @var AuthenticatedAppUser $usuario */
        $usuario = $request->user();

        $tramite = $this->realTramiteRepository->findVisibleForUser($usuario, (int) $id);

        if (! $tramite) {
            return $this->error('Trámite no encontrado.', 404);
        }

        $this->realTramiteRepository->unmarkFollowed($usuario, (int) $tramite['id']);

        return $this->ok([
            'mensaje' => 'Seguimiento eliminado.',
        ]);
    }
}