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

    // Limitar métodos permitidos en lugar de usar '*'
    'allowed_methods' => ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    // Origen del frontend (configurable vía FRONTEND_URL en .env)
    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:5174')],

    'allowed_origins_patterns' => [],

    // Cabeceras permitidas (restringir en producción)
    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'Authorization', 'Accept', 'X-CSRF-TOKEN'],

    'exposed_headers' => [],

    // Cachear preflight por 1 hora
    'max_age' => 3600,

    // Necesario para cookies basadas en sesión si usas Sanctum stateful
    'supports_credentials' => true,

];
