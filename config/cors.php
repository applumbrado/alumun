<?php


//'allowed_origins' => [
//    'http://localhost:6002',
//    'http://localhost:8000',
//    'http://localhost:5173',
//    'http://127.0.0.1:5173',
//    'https://alumbrado.villahermosa.gob.mx',
//    'https://alumbrado.villahermosa.gob.mx:6002',
//    'https://alumbrado.villahermosa.gob.mx:5173',
//    'https://cdn.alumbrado.villahermosa.gob.mx',
//    'https://panel.alumbrado.villahermosa.gob.mx',
//],

return [
    'paths' => ['api/*', 'login', 'logout', 'broadcasting/*', 'sanctum/csrf-cookie','socket.io'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
