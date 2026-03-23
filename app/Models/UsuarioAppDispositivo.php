<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsuarioAppDispositivo extends Model
{
    protected $table = 'usuario_app_dispositivos';

    protected $fillable = [
        'usuario_app_id',
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

    public function usuarioApp()
    {
        return $this->belongsTo(UsuarioApp::class);
    }

    public function scopeActive($query)
    {
        return $query
            ->where('activo', true)
            ->whereNull('invalidado_at');
    }
}
