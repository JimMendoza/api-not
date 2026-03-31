<?php

namespace App\Modules\Notificaciones\Requests;

use App\Support\Http\Requests\ApiRequest;

class InvalidateDispositivoPushRequest extends ApiRequest
{
    public function rules()
    {
        return [
            'deviceId' => ['required', 'string', 'max:191'],
        ];
    }

    public function validationData()
    {
        return array_merge(parent::validationData(), [
            'deviceId' => $this->input('deviceId', $this->input('device_id')),
        ]);
    }
}

