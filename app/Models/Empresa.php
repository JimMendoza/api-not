<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $table = 'empresas';

    protected $fillable = [
        'codigo',
        'nombre',
        'abrv',
        'claims',
        'color',
        'direccion',
        'imagen',
        'ruc',
        'telefono',
        'url',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function usuariosApp()
    {
        return $this->hasMany(UsuarioApp::class);
    }

    public function tramites()
    {
        return $this->hasMany(Tramite::class);
    }
}
