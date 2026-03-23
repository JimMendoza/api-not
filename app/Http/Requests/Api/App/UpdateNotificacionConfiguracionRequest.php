<?php

namespace App\Http\Requests\Api\App;

use App\Models\UsuarioAppNotificacionConfiguracion;

class UpdateNotificacionConfiguracionRequest extends ApiRequest
{
    protected function prepareForValidation()
    {
        $this->merge([
            'zona_horaria' => UsuarioAppNotificacionConfiguracion::ZONA_HORARIA_FIJA,
        ]);
    }

    public function rules()
    {
        return [
            'silenciar_fuera_de_horario' => ['required', 'boolean'],
            'hora_silencio_inicio' => ['required', 'date_format:H:i', 'different:hora_silencio_fin'],
            'hora_silencio_fin' => ['required', 'date_format:H:i', 'different:hora_silencio_inicio'],
            'zona_horaria' => ['required', 'string', 'in:'.UsuarioAppNotificacionConfiguracion::ZONA_HORARIA_FIJA],
            'mostrar_contador_no_leidas' => ['required', 'boolean'],
        ];
    }
}
