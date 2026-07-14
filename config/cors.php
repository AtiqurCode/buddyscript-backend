<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Token auth (Sanctum personal access tokens, not cookies) means a
    // wildcard here wouldn't be a CSRF risk either way, but listing the
    // known frontend origins explicitly is still the tighter default.
    'allowed_origins' => array_values(array_filter(array_unique([
        env('FRONTEND_URL', 'https://buddyscript.test'),
        'https://buddyscripts.netlify.app',
        'http://localhost:5173',
        ...array_filter(array_map('trim', explode(',', (string) env('FRONTEND_URLS', '')))),
    ]))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
