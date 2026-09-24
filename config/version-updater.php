<?php

return [
    'name' => env('ASHIK_VERSION_UPDATER_NAME', 'Ashik Version Update'),
    'prefix' => env('ASHIK_VERSION_UPDATER_PREFIX', 'erp/super-admin'),
    'middleware' => ['web', 'auth', 'super-admin'],
    'layout' => env('ASHIK_VERSION_UPDATER_LAYOUT', 'auto_posts.super_admin.layouts.app'),
    'install_route' => env('ASHIK_INSTALL_ROUTE', 'ashik-install'),
    'build_version' => (int) env('ASHIK_BUILD_VERSION', config('app.build_version', 1)),
    'current_version' => env('ASHIK_CURRENT_VERSION', config('app.current_version', '1.0')),
    'disk' => 'local',
];
