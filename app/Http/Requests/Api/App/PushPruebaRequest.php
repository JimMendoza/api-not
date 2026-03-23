<?php

namespace App\Http\Requests\Api\App;

class PushPruebaRequest extends ApiRequest
{
    public function rules()
    {
        return [
            'tramiteId' => ['nullable', 'integer'],
            'titulo' => ['nullable', 'string', 'max:120'],
            'mensaje' => ['nullable', 'string', 'max:240'],
        ];
    }

    public function validationData()
    {
        return array_merge(parent::validationData(), [
            'tramiteId' => $this->input('tramiteId', $this->input('tramite_id')),
            'titulo' => $this->input('titulo'),
            'mensaje' => $this->input('mensaje'),
        ]);
    }
}

