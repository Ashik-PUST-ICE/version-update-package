<?php

namespace Ashik\VersionUpdater\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class InstallController extends Controller
{
    public function index()
    {
        $requirements = $this->requirements();

        return view('ashik-version-updater::install', [
            'requirements' => $requirements,
            'allPassed' => collect($requirements)->every(fn (array $requirement): bool => $requirement['passed']),
        ]);
    }

    public function configure()
    {
        $requirements = $this->requirements();

        return view('ashik-version-updater::configure', [
            'allPassed' => collect($requirements)->every(fn (array $requirement): bool => $requirement['passed']),
            'purchaseCodeRequired' => (bool) config('version-updater.require_purchase_code', true),
        ]);
    }

    private function requirements(): array
    {
        $extension = static fn (string $name): bool => extension_loaded($name);
        $function = static fn (string $name): bool => function_exists($name);

        return [
            ['name' => 'PHP Version', 'current' => PHP_VERSION, 'required' => '8.2+', 'passed' => version_compare(PHP_VERSION, '8.2.0', '>=')],
            ['name' => 'PDO MySQL', 'current' => $extension('pdo_mysql') ? 'On' : 'Off', 'required' => 'On', 'passed' => $extension('pdo_mysql')],
            ['name' => 'GD Extension', 'current' => $extension('gd') ? 'On' : 'Off', 'required' => 'On', 'passed' => $extension('gd')],
            ['name' => 'cURL Extension', 'current' => $function('curl_version') ? 'On' : 'Off', 'required' => 'On', 'passed' => $function('curl_version')],
            ['name' => 'OpenSSL Extension', 'current' => $extension('openssl') ? 'On' : 'Off', 'required' => 'On', 'passed' => $extension('openssl')],
            ['name' => 'ZIP Extension', 'current' => $extension('zip') ? 'On' : 'Off', 'required' => 'On', 'passed' => $extension('zip')],
            ['name' => 'MBString Extension', 'current' => $extension('mbstring') ? 'On' : 'Off', 'required' => 'On', 'passed' => $extension('mbstring')],
            ['name' => 'XML Extension', 'current' => $extension('xml') ? 'On' : 'Off', 'required' => 'On', 'passed' => $extension('xml')],
            ['name' => 'BCMath Extension', 'current' => $extension('bcmath') ? 'On' : 'Off', 'required' => 'On', 'passed' => $extension('bcmath')],
            ['name' => 'allow_url_fopen', 'current' => ini_get('allow_url_fopen') ? 'On' : 'Off', 'required' => 'On', 'passed' => (bool) ini_get('allow_url_fopen')],
            ['name' => 'Storage writable', 'current' => is_writable(storage_path()) ? 'Writable' : 'Read only', 'required' => 'Writable', 'passed' => is_writable(storage_path())],
            ['name' => 'Cache writable', 'current' => is_writable(storage_path('framework/cache')) ? 'Writable' : 'Read only', 'required' => 'Writable', 'passed' => is_writable(storage_path('framework/cache'))],
            ['name' => 'Application key', 'current' => config('app.key') ? 'Configured' : 'Missing', 'required' => 'Configured', 'passed' => (bool) config('app.key')],
        ];
    }

    public function install(Request $request)
    {
        $rules = [
            'app_name' => ['required', 'string', 'max:120'],
            'app_url' => ['required', 'url', 'max:255'],
        ];

        if (config('version-updater.require_purchase_code', true)) {
            $rules['purchase_code'] = ['required', 'string', 'max:64'];
        }

        $request->validate($rules);

        $purchaseCode = strtoupper(trim((string) $request->input('purchase_code')));

        if (config('version-updater.require_purchase_code', true) && ! $this->validPurchaseCode($purchaseCode)) {
            return back()->withInput()->withErrors([
                'purchase_code' => 'Invalid or already used purchase code.',
            ]);
        }

        if (!collect($this->requirements())->every(fn (array $requirement): bool => $requirement['passed'])) {
            return back()->with('error', 'Please fix all server requirements before installation.');
        }

        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('storage:link');

        if (Schema::hasTable('ashik_installations')) {
            DB::table('ashik_installations')->insert([
                'app_name' => $request->app_name,
                'app_url' => $request->app_url,
                'purchase_code' => $purchaseCode,
                'domain' => parse_url($request->app_url, PHP_URL_HOST),
                'installed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('ashik_purchase_codes')) {
            DB::table('ashik_purchase_codes')
                ->where('code', $purchaseCode)
                ->whereNull('used_at')
                ->update(['used_domain' => parse_url($request->app_url, PHP_URL_HOST), 'used_at' => now(), 'updated_at' => now()]);
        }

        $installData = json_encode([
            'installed_at' => now()->toIso8601String(),
            'app_name' => $request->app_name,
            'app_url' => $request->app_url,
        ], JSON_PRETTY_PRINT);

        file_put_contents(storage_path('ashik-installed'), $installData);
        // Keep compatibility with the host application's existing marker.
        file_put_contents(storage_path('installed'), $installData);

        $destination = Route::has('login') ? route('login') : url('/');

        return redirect()->to($destination)
            ->with('success', 'Ashik system installed successfully.');
    }

    private function validPurchaseCode(string $code): bool
    {
        if ($code === '') {
            return false;
        }

        $masterCode = strtoupper(trim((string) config('version-updater.master_purchase_code')));
        if ($masterCode !== '' && hash_equals($masterCode, $code)) {
            return true;
        }

        $configuredCodes = array_map('strtoupper', config('version-updater.purchase_codes', []));
        if (in_array($code, $configuredCodes, true)) {
            return true;
        }

        if (! Schema::hasTable('ashik_purchase_codes')) {
            return false;
        }

        $record = DB::table('ashik_purchase_codes')->where('code', $code)->where('status', 'active')->first();

        return $record !== null && (config('version-updater.allow_code_reuse', false) || $record->used_at === null);
    }
}
