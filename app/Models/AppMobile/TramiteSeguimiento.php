<?php

namespace App\Models\AppMobile;

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
