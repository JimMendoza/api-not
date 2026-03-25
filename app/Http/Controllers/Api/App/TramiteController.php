<?php

namespace App\Http\Controllers\Api\App;

use App\Repositories\Tramites\RealTramiteRepository;
use App\Services\Auth\AuthenticatedAppUser;
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

        return $this->ok(collect($tramites)->map(function (array $tramite) {
            return $this->tramitePayload($tramite);
        })->values()->all());
    }

    public function show(Request $request, $id)
    {
        /** @var AuthenticatedAppUser $usuario */
        $usuario = $request->user();

        $tramite = $this->realTramiteRepository->findVisibleForUser($usuario, (int) $id);

        if (! $tramite) {
            return $this->error('Trámite no encontrado.', 404);
        }

        return $this->ok($this->tramitePayload($tramite, true));
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

    protected function tramitePayload(array $tramite, bool $includeDescription = false): array
    {
        $payload = [
            'id' => (int) $tramite['id'],
            'codigo' => $tramite['codigo'],
            'titulo' => $tramite['titulo'],
            'fecha' => $tramite['fecha'],
            'estadoActual' => $tramite['estadoActual'],
            'siguiendo' => (bool) $tramite['siguiendo'],
            'notificacionesNoLeidas' => (bool) $tramite['siguiendo']
                ? (int) $tramite['notificacionesNoLeidas']
                : 0,
        ];

        if ($includeDescription) {
            $payload['descripcion'] = $tramite['descripcion'];
        }

        return $payload;
    }
}
