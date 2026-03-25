<?php

namespace App\Models\AppMobile;

class UsuarioDispositivo extends AppMobileModel
{
    protected $fillable = [
        'usuario_id',
        'device_id',
        'push_token',
        'platform',
        'device_name',
        'app_version',
        'activo',
        'ultimo_registro_at',
        'ultimo_push_at',
        'invalidado_at',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'ultimo_registro_at' => 'datetime',
        'ultimo_push_at' => 'datetime',
        'invalidado_at' => 'datetime',
    ];

    protected function baseTable(): string
    {
        return 'usuario_dispositivos';
    }

    public function scopeActive($query)
    {
        return $query
            ->where('activo', true)
            ->whereNull('invalidado_at');
    }
}
