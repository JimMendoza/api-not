<?php

namespace App\Models\AppMobile;

class UsuarioNotificacionConfiguracion extends AppMobileModel
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

    protected $fillable = [
        'usuario_id',
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

    protected function baseTable(): string
    {
        return 'usuario_notificacion_configuraciones';
    }

    public function setZonaHorariaAttribute($value): void
    {
        $this->attributes['zona_horaria'] = self::ZONA_HORARIA_FIJA;
    }

    public static function defaultSettings(): array
    {
        return self::DEFAULT_SETTINGS;
    }

    public static function settingsKeys(): array
    {
        return self::SETTINGS_KEYS;
    }
}
