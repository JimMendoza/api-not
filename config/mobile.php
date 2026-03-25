<?php

return [
    'connection' => env('DB_CONNECTION', 'pgsql'),

    'token_type' => 'Bearer',

    'permission_systems' => [
        'mesa_partes_virtual' => '014',
        'notificaciones' => '009',
    ],

    'modules' => [
        [
            'id' => 'mesa_partes_virtual',
            'nombre' => 'Mesa de Partes Virtual',
            'icono' => 'description',
        ],
        [
            'id' => 'notificaciones',
            'nombre' => 'Notificaciones',
            'icono' => 'notifications',
        ],
    ],
];
