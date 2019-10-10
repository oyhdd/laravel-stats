<?php

return [
    'admin'     => [
        'driver'     => 'local',
        'root'       => storage_path('app/admin'),
        'visibility' => 'public',
        'url'        => env('APP_URL').'/storage',
    ],
];