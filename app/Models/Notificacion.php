<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    protected $table = 'notificaciones';

    protected $fillable = [
        'tramite_id',
        'usuario_app_id',
        'titulo',
        'mensaje',
        'tipo',
        'leida',
        'fecha_hora',
    ];

    protected $casts = [
        'leida' => 'boolean',
        'fecha_hora' => 'datetime',
    ];

    public function tramite()
    {
        return $this->belongsTo(Tramite::class);
    }

    public function usuarioApp()
    {
        return $this->belongsTo(UsuarioApp::class);
    }

    public function scopeVisibleForUsuario($query, UsuarioApp $usuario)
    {
        return $query
            ->where('usuario_app_id', $usuario->id)
            ->whereHas('tramite', function ($tramites) use ($usuario) {
                $tramites->visibleForUsuario($usuario);
            })
            ->whereHas('tramite.seguimientos', function ($seguimientos) use ($usuario) {
                $seguimientos
                    ->where('usuario_app_id', $usuario->id)
                    ->where('activo', true);
            });
    }
}
