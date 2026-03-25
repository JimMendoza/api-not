<?php

namespace App\Models\AppMobile;

class Notificacion extends AppMobileModel
{
    protected $fillable = [
        'usuario_id',
        'tramite_id',
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

    protected function baseTable(): string
    {
        return 'notificaciones';
    }
}
