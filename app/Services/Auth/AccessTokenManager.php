<?php

namespace App\Services\Auth;

use App\Models\AppMobile\UsuarioToken;
use App\Repositories\Identity\RealIdentityRepository;
use Illuminate\Support\Str;

class AccessTokenManager
{
    protected RealIdentityRepository $realIdentityRepository;

    public function __construct(RealIdentityRepository $realIdentityRepository)
    {
        $this->realIdentityRepository = $realIdentityRepository;
    }

    public function issue(AuthenticatedAppUser $usuario): array
    {
        do {
            $plainTextToken = Str::random(80);
            $hashedToken = hash('sha256', $plainTextToken);
        } while ($this->tokenExists($hashedToken));

        $token = UsuarioToken::query()->create([
            'usuario_id' => $usuario->id,
            'empresa_codigo' => $usuario->empresaCodigo,
            'token' => $hashedToken,
            'token_type' => $this->tokenType(),
            'expires_at' => $this->nextExpiration(),
            'last_used_at' => now(),
            'revoked_at' => null,
        ]);

        return [
            'plainTextToken' => $plainTextToken,
            'token' => $token,
        ];
    }

    public function resolve(?string $plainTextToken): ?UsuarioToken
    {
        if (! $plainTextToken) {
            return null;
        }

        $hashedToken = hash('sha256', $plainTextToken);

        $token = UsuarioToken::query()
            ->valid()
            ->where('token', $hashedToken)
            ->first();

        if (! $token) {
            return null;
        }

        $usuario = $this->realIdentityRepository->findUserByTokenContext(
            (int) $token->usuario_id,
            $token->empresa_codigo
        );

        if (! $usuario) {
            return null;
        }

        $token->forceFill([
            'last_used_at' => now(),
            'expires_at' => $this->nextExpiration(),
        ])->save();

        $token->setRelation('authenticatedUser', $usuario);

        return $token;
    }

    public function revoke($token): void
    {
        if (! $token || ! isset($token->id) || $token->revoked_at) {
            return;
        }

        $token->forceFill([
            'revoked_at' => now(),
        ])->save();
    }

    protected function tokenExists(string $hashedToken): bool
    {
        return UsuarioToken::query()
            ->where('token', $hashedToken)
            ->exists();
    }

    protected function tokenType(): string
    {
        return (string) config('mobile.token_type', 'Bearer');
    }

    protected function nextExpiration()
    {
        return now()->addDays($this->tokenTtlDays());
    }

    protected function tokenTtlDays(): int
    {
        return max(1, (int) config('mobile.token_ttl_days', 30));
    }
}
