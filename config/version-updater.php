<?php

return [
    'name' => env('ASHIK_VERSION_UPDATER_NAME', 'Ashik Version Update'),
    'logo' => env('ASHIK_INSTALL_LOGO', 'assets/images/logo.png'),
    'prefix' => env('ASHIK_VERSION_UPDATER_PREFIX', 'erp/super-admin'),
    'middleware' => ['web', 'auth', 'super-admin'],
    'layout' => env('ASHIK_VERSION_UPDATER_LAYOUT', 'auto_posts.super_admin.layouts.app'),
    'install_route' => env('ASHIK_INSTALL_ROUTE', 'ashik-install'),
    'build_version' => (int) env('ASHIK_BUILD_VERSION', config('app.build_version', 1)),
    'current_version' => env('ASHIK_CURRENT_VERSION', config('app.current_version', '1.0')),
    'disk' => 'local',
    'require_purchase_code' => (bool) env('ASHIK_REQUIRE_PURCHASE_CODE', true),
    'master_purchase_code' => env('ASHIK_MASTER_PURCHASE_CODE'),
    'purchase_codes' => array_values(array_filter(array_map('trim', explode(',', (string) env('ASHIK_PURCHASE_CODES', ''))))),
    'allow_code_reuse' => (bool) env('ASHIK_ALLOW_PURCHASE_CODE_REUSE', false),
];
