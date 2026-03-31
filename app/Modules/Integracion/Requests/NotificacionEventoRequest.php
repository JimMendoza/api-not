<?php

namespace App\Modules\Integracion\Requests;

use App\Support\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class NotificacionEventoRequest extends ApiRequest
{
    public const EVENTOS_SOPORTADOS = [
        'tramite_registrado',
        'tramite_derivado',
        'cambio_estado',
        'movimiento_hoja_ruta',
    ];

    public function rules()
    {
        return [
            'tramiteId' => ['required', 'integer'],
            'evento' => ['required', 'string', Rule::in(self::EVENTOS_SOPORTADOS)],
            'payload' => ['nullable', 'array'],
        ];
    }

    public function validationData()
    {
        return [
            'tramiteId' => $this->input('tramiteId', $this->input('tramite_id')),
            'evento' => $this->input('evento'),
            'payload' => $this->input('payload', []),
        ];
    }
}