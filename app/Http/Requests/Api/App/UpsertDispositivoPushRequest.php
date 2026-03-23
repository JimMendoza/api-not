<?php

namespace App\Http\Requests\Api\App;

use Illuminate\Validation\Rule;

class UpsertDispositivoPushRequest extends ApiRequest
{
    public function rules()
    {
        return [
            'deviceId' => ['required', 'string', 'max:191'],
            'pushToken' => ['required', 'string', 'max:4096'],
            'platform' => ['required', 'string', Rule::in(['android', 'ios'])],
            'deviceName' => ['nullable', 'string', 'max:120'],
            'appVersion' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function validationData()
    {
        return array_merge(parent::validationData(), [
            'deviceId' => $this->input('deviceId', $this->input('device_id')),
            'pushToken' => $this->input('pushToken', $this->input('push_token')),
            'platform' => $this->input('platform'),
            'deviceName' => $this->input('deviceName', $this->input('device_name')),
            'appVersion' => $this->input('appVersion', $this->input('app_version')),
        ]);
    }
}

