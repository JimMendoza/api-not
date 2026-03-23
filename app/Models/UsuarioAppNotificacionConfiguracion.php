<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsuarioAppNotificacionConfiguracion extends Model
{
    const ZONA_HORARIA_FIJA = 'America/Lima';

    const SETTINGS_KEYS = [
        'silenciar_fuera_de_horario',
        'hora_silencio_inicio',
        'hora_silencio_fin',
        'zona_horaria',
        'mostrar_contador_no_leidas',
    ];

    const DEFAULT_SETTINGS = [
        'silenciar_fuera_de_horario' => false,
        'hora_silencio_inicio' => '22:00',
        'hora_silencio_fin' => '07:00',
        'zona_horaria' => self::ZONA_HORARIA_FIJA,
        'mostrar_contador_no_leidas' => true,
    ];

    protected $table = 'usuario_app_notificacion_configuraciones';

    protected $fillable = [
        'usuario_app_id',
        'silenciar_fuera_de_horario',
        'hora_silencio_inicio',
        'hora_silencio_fin',
        'zona_horaria',
        'mostrar_contador_no_leidas',
    ];

    protected $casts = [
        'silenciar_fuera_de_horario' => 'boolean',
        'mostrar_contador_no_leidas' => 'boolean',
    ];

    public function usuarioApp()
    {
        return $this->belongsTo(UsuarioApp::class);
    }

    public function setZonaHorariaAttribute($value)
    {
        $this->attributes['zona_horaria'] = self::ZONA_HORARIA_FIJA;
    }

    public static function defaultSettings()
    {
        return self::DEFAULT_SETTINGS;
    }

    public static function settingsKeys()
    {
        return self::SETTINGS_KEYS;
    }
}
