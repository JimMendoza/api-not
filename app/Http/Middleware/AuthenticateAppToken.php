<?php

namespace App\Http\Middleware;

use App\Support\App\Auth\AccessTokenManager;
use Closure;

class AuthenticateAppToken
{
    protected $accessTokenManager;

    public function __construct(AccessTokenManager $accessTokenManager)
    {
        $this->accessTokenManager = $accessTokenManager;
    }

    public function handle($request, Closure $next)
    {
        $token = $this->accessTokenManager->resolve($request->bearerToken());

        if (! $token) {
            return response()->json([
                'mensaje' => 'No autenticado.',
            ], 401);
        }

        $request->attributes->set('appToken', $token);
        $request->setUserResolver(function () use ($token) {
            return $token->usuarioApp;
        });

        return $next($request);
    }
}
