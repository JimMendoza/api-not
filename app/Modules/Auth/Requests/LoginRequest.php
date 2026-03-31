<?php

namespace App\Modules\Auth\Requests;

use App\Support\Http\Requests\ApiRequest;

class LoginRequest extends ApiRequest
{
    public function rules()
    {
        return [
            'username' => 'required|string|max:50',
            'password' => 'required|string|max:255',
            'codEmp' => 'required|string|max:20',
        ];
    }
}
