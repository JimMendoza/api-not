<?php

namespace App\Modules\Auth\Services;

use App\Modules\Auth\Models\UsuarioToken;
use App\Modules\Auth\Repositories\RealIdentityRepository;
use App\Modules\Auth\Support\AuthenticatedAppUser;
use Illuminate\Support\Str;

class AccessTokenManager
{
    protected RealIdentityRepository $realIdentityRepository;

    public function __construct(RealIdentityRepository $realIdentityRepository)
    {
        $this->realIdentityRepository = $realIdentityRepository;
    }

    public function issue(AuthenticatedAppUser $usuario, string $deviceId): array
    {
        $empresaCodigo = trim((string) $usuario->empresaCodigo);
        $normalizedDeviceId = $this->normalizeDeviceId($deviceId);

        $this->revokeActiveByDeviceContext(
            (int) $usuario->id,
            $empresaCodigo,
            $normalizedDeviceId
        );

        do {
            $plainTextToken = Str::random(80);
            $hashedToken = hash('sha256', $plainTextToken);
        } while ($this->tokenExists($hashedToken));

        $token = UsuarioToken::query()->create([
            'usuario_id' => $usuario->id,
            'empresa_codigo' => $empresaCodigo,
            'device_id' => $normalizedDeviceId,
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

        $empresaCodigo = trim((string) $token->empresa_codigo);

        if ($empresaCodigo === '') {
            return null;
        }

        $usuario = $this->realIdentityRepository->findUserByTokenContext(
            (int) $token->usuario_id,
            $empresaCodigo
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

    protected function normalizeDeviceId(string $deviceId): string
    {
        return trim($deviceId);
    }

    protected function revokeActiveByDeviceContext(
        int $usuarioId,
        string $empresaCodigo,
        string $deviceId
    ): void {
        UsuarioToken::query()
            ->where('usuario_id', $usuarioId)
            ->where('empresa_codigo', $empresaCodigo)
            ->where('device_id', $deviceId)
            ->whereNull('revoked_at')
            ->update([
                'revoked_at' => now(),
                'updated_at' => now(),
            ]);
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
