<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | api-not authenticates mobile clients through the custom `app.token`
    | middleware and tokens stored in `app_mobile.usuario_tokens`. This file
    | remains only for Laravel's internal web/session services and is not part
    | of the mobile runtime contract.
    |
    */

    'defaults' => [
        'guard' => 'web',
        'passwords' => 'framework_users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | The mobile API does not use Laravel guards. A minimal web guard is kept
    | so the framework can bootstrap its standard authentication services.
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'framework_users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | This provider is framework-only support. The mobile runtime resolves the
    | authenticated user through RealIdentityRepository and AccessTokenManager.
    |
    */

    'providers' => [
        'framework_users' => [
            'driver' => 'database',
            'table' => 'users',
        ],
    ],

    'passwords' => [
        'framework_users' => [
            'provider' => 'framework_users',
            'table' => 'password_resets',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,

];