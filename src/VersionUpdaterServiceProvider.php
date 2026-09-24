<?php

namespace Ashik\VersionUpdater;

use Illuminate\Support\ServiceProvider;

class VersionUpdaterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/version-updater.php', 'version-updater');
        $this->app->singleton(VersionManager::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\PurchaseCodeCommand::class,
            ]);
        }

        $this->publishes([
            __DIR__ . '/../config/version-updater.php' => config_path('version-updater.php'),
        ], 'ashik-version-updater-config');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'ashik-version-updater');
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}
