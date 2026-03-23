<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TramiteSeguimiento extends Model
{
    protected $table = 'tramite_seguimientos';

    protected $fillable = [
        'tramite_id',
        'usuario_app_id',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function tramite()
    {
        return $this->belongsTo(Tramite::class);
    }

    public function usuarioApp()
    {
        return $this->belongsTo(UsuarioApp::class);
    }
}
