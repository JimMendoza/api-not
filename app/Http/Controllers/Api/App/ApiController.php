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
        return array_values(config('mobile.modules', []));
    }

    protected function appPermissions()
    {
        $configuredSystems = config('mobile.permission_systems', []);

        if (! empty($configuredSystems)) {
            return array_values(array_keys($configuredSystems));
        }

        return collect($this->appModules())->pluck('id')->values()->all();
    }

    protected function appPermissionsForSystemCodes(array $systemCodes)
    {
        $mappedPermissions = [];

        foreach (config('mobile.permission_systems', []) as $permission => $systemCode) {
            if (in_array((string) $systemCode, $systemCodes, true)) {
                $mappedPermissions[] = $permission;
            }
        }

        return array_values($mappedPermissions);
    }
}
