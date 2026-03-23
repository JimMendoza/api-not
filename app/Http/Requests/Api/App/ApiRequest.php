<?php

namespace App\Http\Requests\Api\App;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class ApiRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'mensaje' => 'Datos inválidos.',
            'errores' => $validator->errors(),
        ], 422));
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(response()->json([
            'mensaje' => 'No autorizado.',
        ], 403));
    }
}
