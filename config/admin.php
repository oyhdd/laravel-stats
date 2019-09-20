<?php

return [
    'name' => 'stats',

    'logo' => '<b>模调系统</b>',

    'logo-mini' => '<b>Stats</b>',

    'layout' => ['sidebar-mini'],

    'grid_action_class' => \Oyhdd\StatsCenter\Extensions\Actions::class,

    'bootstrap' => dirname(__DIR__).'/src/Http/bootstrap.php',

    /*
    |--------------------------------------------------------------------------
    | Application Skin
    |--------------------------------------------------------------------------
    |
    | This value is the skin of admin pages.
    | @see https://adminlte.io/docs/2.4/layout
    |
    | Supported:
    |    "skin-blue", "skin-blue-light", "skin-yellow", "skin-yellow-light",
    |    "skin-green", "skin-green-light", "skin-purple", "skin-purple-light",
    |    "skin-red", "skin-red-light", "skin-black", "skin-black-light".
    |
    */
    'skin' => 'skin-blue',

    // 'route' => [
    //     'prefix' => env('ADMIN_ROUTE_PREFIX', 'admin'),
    //     'namespace' => 'App\\Admin\\Controllers',
    //     'middleware' => ['web', 'admin'],
    // ],
];
