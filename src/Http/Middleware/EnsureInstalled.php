<?php

namespace Ashik\VersionUpdater\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureInstalled
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->is(config('version-updater.install_route', 'ashik-install') . '*')) {
            return $next($request);
        }

        if (is_file(storage_path('installed')) || is_file(storage_path('ashik-installed'))) {
            return $next($request);
        }

        return redirect()->route('ashik.install');
    }
}
