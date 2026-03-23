<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Requests\Api\App\UpdateNotificacionConfiguracionRequest;
use App\Models\UsuarioApp;
use App\Models\UsuarioAppNotificacionConfiguracion;
use Illuminate\Http\Request;

class NotificacionConfiguracionController extends ApiController
{
    public function show(Request $request)
    {
        /** @var UsuarioApp $usuario */
        $usuario = $request->user();

        $configuracion = UsuarioAppNotificacionConfiguracion::query()
            ->where('usuario_app_id', $usuario->id)
            ->first();

        return $this->ok($this->toPayload($configuracion));
    }

    public function update(UpdateNotificacionConfiguracionRequest $request)
    {
        /** @var UsuarioApp $usuario */
        $usuario = $request->user();

        $configuracion = UsuarioAppNotificacionConfiguracion::query()->updateOrCreate(
            [
                'usuario_app_id' => $usuario->id,
            ],
            $request->validated()
        );

        return $this->ok($this->toPayload($configuracion));
    }

    protected function toPayload(UsuarioAppNotificacionConfiguracion $configuracion = null)
    {
        $defaults = UsuarioAppNotificacionConfiguracion::defaultSettings();

        if (! $configuracion) {
            return $defaults;
        }

        return array_merge(
            $defaults,
            $configuracion->only(UsuarioAppNotificacionConfiguracion::settingsKeys())
        );
    }
}
