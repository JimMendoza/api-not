<?php

namespace App\Http\Middleware;

use App\Services\Auth\AccessTokenManager;
use Closure;
use Illuminate\Database\Eloquent\Model;

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
            if ($token instanceof Model && $token->relationLoaded('authenticatedUser')) {
                return $token->getRelation('authenticatedUser');
            }

            return $token->authenticatedUser ?? null;
        });

        return $next($request);
    }
}
