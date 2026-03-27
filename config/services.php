<?php

return [

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