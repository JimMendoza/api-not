<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class UsuarioApp extends Authenticatable
{
    use Notifiable;

    protected $table = 'usuarios_app';

    protected $fillable = [
        'empresa_id',
        'username',
        'full_name',
        'password',
        'activo',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function tokens()
    {
        return $this->hasMany(UsuarioAppToken::class);
    }

    public function tramites()
    {
        return $this->hasMany(Tramite::class);
    }

    public function seguimientos()
    {
        return $this->hasMany(TramiteSeguimiento::class);
    }

    public function notificaciones()
    {
        return $this->hasMany(Notificacion::class);
    }

    public function configuracionNotificaciones()
    {
        return $this->hasOne(UsuarioAppNotificacionConfiguracion::class);
    }

    public function dispositivosPush()
    {
        return $this->hasMany(UsuarioAppDispositivo::class);
    }
}
