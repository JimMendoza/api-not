<?php

namespace App\Modules\Auth\Requests;

use App\Support\Http\Requests\ApiRequest;

class LoginRequest extends ApiRequest
{
    protected function prepareForValidation()
    {
        $deviceId = $this->input(
            'deviceId',
            $this->input('device_id', $this->header('X-App-Device-Id'))
        );

        if (is_string($deviceId)) {
            $deviceId = trim($deviceId);
        }

        $this->merge([
            'deviceId' => $deviceId,
        ]);
    }

    public function rules()
    {
        return [
            'codUsuario' => 'required|string|max:50',
            'password' => 'required|string|max:255',
            'codEmp' => 'required|string|max:20',
            'deviceId' => 'required|string|max:191',
        ];
    }
}
