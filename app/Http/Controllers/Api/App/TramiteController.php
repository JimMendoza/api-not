<?php

namespace App\Http\Controllers\Api\App;

use App\Models\Tramite;
use App\Models\TramiteMovimiento;
use App\Models\TramiteSeguimiento;
use App\Models\UsuarioApp;
use Illuminate\Http\Request;

class TramiteController extends ApiController
{
    public function index(Request $request)
    {
        /** @var UsuarioApp $usuario */
        $usuario = $request->user();

        $tramites = $this->tramitesQuery($usuario)
            ->orderByDesc('fecha_registro')
            ->orderByDesc('id')
            ->get();

        return $this->ok($tramites->map(function (Tramite $tramite) {
            return $this->tramitePayload($tramite);
        })->values()->all());
    }

    public function show(Request $request, $id)
    {
        /** @var UsuarioApp $usuario */
        $usuario = $request->user();
        $tramite = $this->tramitesQuery($usuario)->where('id', $id)->first();

        if (! $tramite) {
            return $this->error('Trámite no encontrado.', 404);
        }

        return $this->ok($this->tramitePayload($tramite, true));
    }

    public function hojaRuta(Request $request, $id)
    {
        /** @var UsuarioApp $usuario */
        $usuario = $request->user();
        $tramite = Tramite::query()
            ->visibleForUsuario($usuario)
            ->where('id', $id)
            ->first();

        if (! $tramite) {
            return $this->error('Trámite no encontrado.', 404);
        }

        $movimientos = $tramite->movimientos()
            ->orderBy('fecha_hora')
            ->get();

        return $this->ok($movimientos->map(function (TramiteMovimiento $movimiento) {
            return [
                'fechaHora' => optional($movimiento->fecha_hora)->format('Y-m-d H:i'),
                'nroDoc' => $movimiento->nro_doc,
                'destino' => $movimiento->destino,
                'estado' => $movimiento->estado,
            ];
        })->values()->all());
    }

    public function seguir(Request $request, $id)
    {
        /** @var UsuarioApp $usuario */
        $usuario = $request->user();
        $tramite = Tramite::query()
            ->visibleForUsuario($usuario)
            ->where('id', $id)
            ->first();

        if (! $tramite) {
            return $this->error('Trámite no encontrado.', 404);
        }

        TramiteSeguimiento::query()->updateOrCreate(
            [
                'tramite_id' => $tramite->id,
                'usuario_app_id' => $usuario->id,
            ],
            [
                'activo' => true,
            ]
        );

        return $this->ok([
            'mensaje' => 'Trámite marcado para seguimiento.',
        ]);
    }

    public function dejarSeguir(Request $request, $id)
    {
        /** @var UsuarioApp $usuario */
        $usuario = $request->user();
        $tramite = Tramite::query()
            ->visibleForUsuario($usuario)
            ->where('id', $id)
            ->first();

        if (! $tramite) {
            return $this->error('Trámite no encontrado.', 404);
        }

        $seguimiento = TramiteSeguimiento::query()
            ->where('tramite_id', $tramite->id)
            ->where('usuario_app_id', $usuario->id)
            ->first();

        if ($seguimiento) {
            $seguimiento->forceFill([
                'activo' => false,
            ])->save();
        }

        return $this->ok([
            'mensaje' => 'Seguimiento eliminado.',
        ]);
    }

    protected function tramitesQuery(UsuarioApp $usuario)
    {
        return Tramite::query()
            ->visibleForUsuario($usuario)
            ->withCount([
                'seguimientos as siguiendo_count' => function ($query) use ($usuario) {
                    $query
                        ->where('usuario_app_id', $usuario->id)
                        ->where('activo', true);
                },
                'notificaciones as notificaciones_no_leidas_count' => function ($query) use ($usuario) {
                    $query
                        ->where('usuario_app_id', $usuario->id)
                        ->where('leida', false);
                },
            ]);
    }

    protected function tramitePayload(Tramite $tramite, $includeDescription = false)
    {
        $siguiendo = (int) $tramite->siguiendo_count > 0;

        $payload = [
            'id' => $tramite->id,
            'codigo' => $tramite->codigo,
            'titulo' => $tramite->titulo,
            'fecha' => optional($tramite->fecha_registro)->format('Y-m-d'),
            'estadoActual' => $tramite->estado_actual,
            'siguiendo' => $siguiendo,
            'notificacionesNoLeidas' => $siguiendo ? (int) $tramite->notificaciones_no_leidas_count : 0,
        ];

        if ($includeDescription) {
            $payload['descripcion'] = $tramite->descripcion;
        }

        return $payload;
    }
}
