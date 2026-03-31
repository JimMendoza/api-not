<?php

namespace App\Modules\Notificaciones\Services;

use App\Modules\Notificaciones\Models\UsuarioDispositivo;

interface PushSender
{
    public function provider();

    public function isConfigured();

    public function send(UsuarioDispositivo $dispositivo, array $message);
}
