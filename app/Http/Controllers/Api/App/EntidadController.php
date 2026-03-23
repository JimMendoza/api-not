<?php

namespace App\Http\Controllers\Api\App;

use App\Models\Empresa;

class EntidadController extends ApiController
{
    public function index()
    {
        $empresas = Empresa::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return $this->ok($empresas->map(function (Empresa $empresa) {
            return [
                'codigo' => $empresa->codigo,
                // Alias transicional para clientes antiguos.
                'id' => $empresa->codigo,
                'nombre' => $empresa->nombre,
                'imagen' => $empresa->imagen,
            ];
        })->values()->all());
    }
}
