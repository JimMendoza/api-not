<?php

namespace App\Models\AppMobile;

class UsuarioToken extends AppMobileModel
{
    protected $fillable = [
        'usuario_id',
        'empresa_codigo',
        'token',
        'token_type',
        'expires_at',
        'last_used_at',
        'revoked_at',
    ];

    protected $hidden = [
        'token',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    protected function baseTable(): string
    {
        return 'usuario_tokens';
    }

    public function scopeValid($query)
    {
        return $query
            ->whereNull('revoked_at')
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now());
    }
}
