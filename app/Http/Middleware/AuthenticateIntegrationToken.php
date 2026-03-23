<?php

namespace App\Http\Middleware;

use Closure;

class AuthenticateIntegrationToken
{
    public function handle($request, Closure $next)
    {
        $expectedToken = (string) config('services.integration.token', '');

        if ($expectedToken === '') {
            return response()->json([
                'mensaje' => 'Integración no configurada.',
            ], 503);
        }

        $providedToken = $request->header('X-Integracion-Token');

        if (! $providedToken) {
            $providedToken = $this->extractBearerToken($request->header('Authorization'));
        }

        if (! is_string($providedToken) || ! hash_equals($expectedToken, $providedToken)) {
            return response()->json([
                'mensaje' => 'No autorizado.',
            ], 401);
        }

        return $next($request);
    }

    protected function extractBearerToken($authorizationHeader)
    {
        if (! is_string($authorizationHeader)) {
            return null;
        }

        if (preg_match('/^\s*Bearer\s+(.+)$/i', $authorizationHeader, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}

