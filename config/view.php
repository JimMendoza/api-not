<?php

return [

    /*
    |--------------------------------------------------------------------------
    | View Storage Paths
    |--------------------------------------------------------------------------
    |
    | api-not is API-only and does not ship application Blade views. This
    | configuration remains because Laravel still boots the ViewServiceProvider
    | for framework-level rendering and exception handling support.
    |
    */

    'paths' => array_values(array_filter([
        resource_path('views'),
    ], 'is_dir')),

    /*
    |--------------------------------------------------------------------------
    | Compiled View Path
    |--------------------------------------------------------------------------
    |
    | This option determines where any compiled Blade templates would be
    | stored if the framework needs them.
    |
    */

    'compiled' => env(
        'VIEW_COMPILED_PATH',
        realpath(storage_path('framework/views'))
    ),

];