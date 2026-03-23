<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'integration' => [
        'token' => env('INTEGRACION_API_TOKEN'),
    ],
    'fcm' => [
        'enabled' => env('FCM_ENABLED', false),
        'project_id' => env('FCM_PROJECT_ID'),
        'service_account_json' => env('FCM_SERVICE_ACCOUNT_JSON'),
        'service_account_json_path' => env('FCM_SERVICE_ACCOUNT_JSON_PATH'),
        'oauth_token_url' => env('FCM_OAUTH_TOKEN_URL', 'https://oauth2.googleapis.com/token'),
        'send_url' => env('FCM_SEND_URL', 'https://fcm.googleapis.com/v1/projects/{project}/messages:send'),
    ],

];

