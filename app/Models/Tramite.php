<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tramite extends Model
{
    protected $table = 'tramites';

    protected $fillable = [
        'empresa_id',
        'usuario_app_id',
        'codigo',
        'titulo',
        'descripcion',
        'fecha_registro',
        'estado_actual',
        'activo',
    ];

    protected $casts = [
        'fecha_registro' => 'date',
        'activo' => 'boolean',
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function usuarioApp()
    {
        return $this->belongsTo(UsuarioApp::class);
    }

    public function movimientos()
    {
        return $this->hasMany(TramiteMovimiento::class);
    }

    public function seguimientos()
    {
        return $this->hasMany(TramiteSeguimiento::class);
    }

    public function notificaciones()
    {
        return $this->hasMany(Notificacion::class);
    }

    public function scopeVisibleForUsuario($query, UsuarioApp $usuario)
    {
        return $query
            ->where('empresa_id', $usuario->empresa_id)
            ->where('usuario_app_id', $usuario->id)
            ->where('activo', true);
    }
}
