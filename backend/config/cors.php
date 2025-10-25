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

    // Orígenes permitidos (configurable vía CORS_ALLOWED_ORIGINS en .env)
    // Puede ser una lista separada por comas o '*' para permitir todos en desarrollo.
    'allowed_origins' => function_exists('env') ? (function() {
        $cfg = env('CORS_ALLOWED_ORIGINS', env('FRONTEND_URL', 'http://localhost:5174'));
        if ($cfg === '*') return ['*'];
        return array_map('trim', explode(',', $cfg));
    })() : [env('FRONTEND_URL', 'http://localhost:5174')],

    'allowed_origins_patterns' => [],

    // Cabeceras permitidas (restringir en producción)
    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'Authorization', 'Accept', 'X-CSRF-TOKEN'],

    'exposed_headers' => [],

    // Cachear preflight por 1 hora
    'max_age' => 3600,

    // Necesario para cookies basadas en sesión si usas Sanctum stateful
    'supports_credentials' => true,

];
