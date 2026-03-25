<?php

namespace App\Services\Auth;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

class AuthenticatedAppUser implements AuthenticatableContract
{
    use Authenticatable;

    public $id;
    public $username;
    public $codUsuario;
    public $cod_usuario;
    public $fullName;
    public $full_name;
    public $password;
    public $empresaCodigo;
    public $empresa_codigo;
    public $empresa_id;
    public $empresaNombre;
    public $empresa_nombre;
    public $empresaImagen;
    public $empresa_imagen;
    public $permisos = [];

    public function __construct(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->{$key} = $value;
        }

        $this->codUsuario = $this->codUsuario ?? $this->username;
        $this->cod_usuario = $this->cod_usuario ?? $this->codUsuario;
        $this->fullName = $this->fullName ?? $this->full_name ?? $this->username;
        $this->full_name = $this->full_name ?? $this->fullName;
        $this->empresaCodigo = $this->empresaCodigo ?? $this->empresa_codigo ?? null;
        $this->empresa_codigo = $this->empresa_codigo ?? $this->empresaCodigo;
        $this->empresa_id = $this->empresa_id ?? $this->empresaCodigo;
        $this->empresaNombre = $this->empresaNombre ?? $this->empresa_nombre ?? null;
        $this->empresa_nombre = $this->empresa_nombre ?? $this->empresaNombre;
        $this->empresaImagen = $this->empresaImagen ?? $this->empresa_imagen ?? null;
        $this->empresa_imagen = $this->empresa_imagen ?? $this->empresaImagen;
        $this->permisos = array_values($this->permisos ?? []);
    }
}
