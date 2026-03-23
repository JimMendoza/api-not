<?php

namespace App\Support\App\Auth;

use App\Models\UsuarioApp;
use App\Models\UsuarioAppToken;
use Illuminate\Support\Str;

class AccessTokenManager
{
    public function issue(UsuarioApp $usuario)
    {
        do {
            $plainTextToken = Str::random(80);
            $hashedToken = hash('sha256', $plainTextToken);
        } while (UsuarioAppToken::query()->where('token', $hashedToken)->exists());

        $token = $usuario->tokens()->create([
            'token' => $hashedToken,
            'token_type' => $this->tokenType(),
            'expires_at' => null,
            'last_used_at' => now(),
        ]);

        return [
            'plainTextToken' => $plainTextToken,
            'token' => $token,
        ];
    }

    public function resolve($plainTextToken)
    {
        if (! $plainTextToken) {
            return null;
        }

        $token = UsuarioAppToken::query()
            ->with(['usuarioApp.empresa'])
            ->valid()
            ->where('token', hash('sha256', $plainTextToken))
            ->first();

        if (! $token) {
            return null;
        }

        $usuario = $token->usuarioApp;

        if (! $usuario || ! $usuario->activo || ! $usuario->empresa || ! $usuario->empresa->activo) {
            return null;
        }

        $token->forceFill([
            'last_used_at' => now(),
        ])->save();

        return $token;
    }

    public function revoke(UsuarioAppToken $token)
    {
        if ($token->revoked_at) {
            return;
        }

        $token->forceFill([
            'revoked_at' => now(),
        ])->save();
    }

    protected function tokenType()
    {
        return (string) config('app_mobile.token_type', 'Bearer');
    }
}
