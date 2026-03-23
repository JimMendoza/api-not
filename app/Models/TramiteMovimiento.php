<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TramiteMovimiento extends Model
{
    protected $table = 'tramite_movimientos';

    protected $fillable = [
        'tramite_id',
        'fecha_hora',
        'nro_doc',
        'destino',
        'estado',
        'observacion',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
    ];

    public function tramite()
    {
        return $this->belongsTo(Tramite::class);
    }
}
