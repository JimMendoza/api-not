<?php

namespace App\Services\Push;

use App\Models\AppMobile\UsuarioDispositivo;

interface PushSender
{
    public function provider();

    public function isConfigured();

    public function send(UsuarioDispositivo $dispositivo, array $message);
}
