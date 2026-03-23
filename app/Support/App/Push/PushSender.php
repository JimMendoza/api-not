<?php

namespace App\Support\App\Push;

use App\Models\UsuarioAppDispositivo;

interface PushSender
{
    public function provider();

    public function isConfigured();

    public function send(UsuarioAppDispositivo $dispositivo, array $message);
}
