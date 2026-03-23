<?php

namespace App\Http\Controllers\Api\App;

use App\Models\Notificacion;
use App\Models\UsuarioApp;
use Illuminate\Http\Request;

class NotificacionController extends ApiController
{
    public function index(Request $request)
    {
        /** @var UsuarioApp $usuario */
        $usuario = $request->user();

        $notificaciones = Notificacion::query()
            ->visibleForUsuario($usuario)
            ->with('tramite')
            ->orderBy('leida')
            ->orderByDesc('fecha_hora')
            ->get();

        return $this->ok($notificaciones->map(function (Notificacion $notificacion) {
            return $this->notificacionPayload($notificacion);
        })->values()->all());
    }

    public function resumen(Request $request)
    {
        /** @var UsuarioApp $usuario */
        $usuario = $request->user();

        $noLeidas = Notificacion::query()
            ->visibleForUsuario($usuario)
            ->where('leida', false)
            ->count();

        return $this->ok([
            'noLeidas' => $noLeidas,
        ]);
    }

    public function marcarLeida(Request $request, $id)
    {
        /** @var UsuarioApp $usuario */
        $usuario = $request->user();

        $notificacion = Notificacion::query()
            ->visibleForUsuario($usuario)
            ->where('id', $id)
            ->first();

        if (! $notificacion) {
            return $this->error('Notificación no encontrada.', 404);
        }

        $notificacion->forceFill([
            'leida' => true,
        ])->save();

        return $this->ok([
            'mensaje' => 'Notificación marcada como leída.',
        ]);
    }

    protected function notificacionPayload(Notificacion $notificacion)
    {
        return [
            'id' => $notificacion->id,
            'tramiteId' => (int) $notificacion->tramite_id,
            'codigoTramite' => optional($notificacion->tramite)->codigo,
            'titulo' => $notificacion->titulo,
            'mensaje' => $notificacion->mensaje,
            'tipo' => $notificacion->tipo,
            'leida' => (bool) $notificacion->leida,
            'fechaHora' => optional($notificacion->fecha_hora)->format('Y-m-d H:i'),
        ];
    }
}
