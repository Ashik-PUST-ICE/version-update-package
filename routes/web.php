<?php

use Ashik\VersionUpdater\Http\Controllers\VersionUpdateController;
use Ashik\VersionUpdater\Http\Controllers\InstallController;
use Ashik\VersionUpdater\Http\Middleware\EnsureInstalled;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])
    ->prefix(config('version-updater.install_route', 'ashik-install'))
    ->as('ashik.')
    ->group(function () {
        Route::get('/', [InstallController::class, 'index'])->name('install');
        Route::post('/', [InstallController::class, 'install'])->name('install.store');
    });

Route::middleware(array_merge([EnsureInstalled::class], config('version-updater.middleware', ['web', 'auth'])))
    ->prefix(config('version-updater.prefix', 'erp/super-admin'))
    ->as('ashik.')
    ->group(function () {
        Route::get('version-update', [VersionUpdateController::class, 'index'])->name('version-update');
        Route::post('version-update', [VersionUpdateController::class, 'upload'])->name('version-update.upload');
        Route::post('version-update/apply', [VersionUpdateController::class, 'apply'])->name('version-update.apply');
        Route::delete('version-update', [VersionUpdateController::class, 'delete'])->name('version-update.delete');
    });
