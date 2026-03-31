<?php

namespace App\Modules\Tramites\Models;

use App\Support\Models\AppMobileModel;

class TramiteSeguimiento extends AppMobileModel
{
    protected $fillable = [
        'usuario_id',
        'tramite_id',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    protected function baseTable(): string
    {
        return 'tramite_seguimientos';
    }
}
