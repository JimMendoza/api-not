<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;

abstract class ApiController extends Controller
{
    protected function ok($data, $status = 200)
    {
        return response()->json($data, $status);
    }

    protected function error($message, $status)
    {
        return response()->json([
            'mensaje' => $message,
        ], $status);
    }

    protected function appModules()
    {
        return array_values(config('app_mobile.modules', []));
    }

    protected function appPermissions()
    {
        return collect($this->appModules())->pluck('id')->values()->all();
    }
}
