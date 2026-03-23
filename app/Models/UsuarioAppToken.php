<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsuarioAppToken extends Model
{
    protected $table = 'usuario_app_tokens';

    protected $fillable = [
        'usuario_app_id',
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

    public function usuarioApp()
    {
        return $this->belongsTo(UsuarioApp::class);
    }

    public function scopeValid($query)
    {
        return $query
            ->whereNull('revoked_at')
            ->where(function ($subQuery) {
                $subQuery
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
