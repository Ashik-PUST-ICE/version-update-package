<?php

namespace Ashik\VersionUpdater\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;

class InstallController extends Controller
{
    public function index()
    {
        return view('ashik-version-updater::install', [
            'requirements' => [
                'PHP 8.2+' => version_compare(PHP_VERSION, '8.2.0', '>='),
                'Storage writable' => is_writable(storage_path()),
                'Cache writable' => is_writable(storage_path('framework/cache')),
                'App key configured' => (bool) config('app.key'),
            ],
        ]);
    }

    public function install(Request $request)
    {
        $request->validate([
            'app_name' => ['required', 'string', 'max:120'],
            'app_url' => ['required', 'url', 'max:255'],
        ]);

        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('storage:link');

        $installData = json_encode([
            'installed_at' => now()->toIso8601String(),
            'app_name' => $request->app_name,
            'app_url' => $request->app_url,
        ], JSON_PRETTY_PRINT);

        file_put_contents(storage_path('ashik-installed'), $installData);
        // Keep compatibility with the host application's existing marker.
        file_put_contents(storage_path('installed'), $installData);

        return back()
            ->with('success', 'Ashik system installed successfully.');
    }
}
