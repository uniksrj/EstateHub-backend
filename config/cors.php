<?php

$allowedOrigins = array_values(array_filter(array_map(
    'trim',
    explode(',', env('CORS_ALLOWED_ORIGINS', env('FRONTEND_URL', 'http://localhost:5173')))
)));

return [
    'paths' => ['api/*','admin/*', 'sanctum/csrf-cookie', 'login', 'logout','broadcasting/*'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_origins' => $allowedOrigins,
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,    
];
