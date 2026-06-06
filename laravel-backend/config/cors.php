<?php

$envOrigins = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))
)));

$defaultOrigins = array_values(array_filter(array_unique(array_merge(
    [
        env('FRONTEND_URL'),
        env('LANDING_URL'),
        'http://localhost:5173',
        'http://127.0.0.1:5173',
        'http://wails.localhost',
        'https://wails.localhost',
        'wails://wails',
        'wails://wails.localhost',
    ],
    $envOrigins
))));

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => env('CORS_ALLOW_ALL', false)
        ? ['*']
        : $defaultOrigins,

    'allowed_origins_patterns' => env('CORS_ALLOW_ALL', false) ? [] : [
        '/^https?:\/\/(localhost|127\.0\.0\.1|wails\.localhost)(:\d+)?$/',
        '/^wails:\/\/wails(\.localhost)?(:\d+)?$/',
        '/^https?:\/\/([a-z0-9-]+\.)?nessapos\.(com|my\.id)$/',
        '/^https:\/\/[a-z0-9-]+([-.][a-z0-9-]+)*\.vercel\.app$/',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
