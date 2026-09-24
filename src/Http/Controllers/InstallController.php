<?php

namespace Ashik\VersionUpdater\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Ashik\VersionUpdater\Support\InstallLogger;

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

    public function prepareDatabase(Request $request)
    {
        $rules = [
            'app_name' => ['required', 'string', 'max:120'],
            'app_url' => ['required', 'url', 'max:255'],
        ];
        if (config('version-updater.require_purchase_code', true)) {
            $rules['purchase_code'] = ['required', 'string', 'max:64'];
        }

        $data = $request->validate($rules);
        $data['purchase_code'] = strtoupper(trim((string) ($data['purchase_code'] ?? '')));

        if (config('version-updater.require_purchase_code', true) && ! $this->validPurchaseCode($data['purchase_code'])) {
            return back()->withInput()->withErrors(['purchase_code' => 'Invalid or already used purchase code.']);
        }

        session(['ashik.install' => $data]);
        InstallLogger::write('Application details accepted', ['app_url' => $data['app_url']]);

        return redirect()->route('ashik.install.database');
    }

    public function database()
    {
        return view('ashik-version-updater::database', [
            'setup' => session('ashik.install', []),
            'database' => [
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '3306'),
                'name' => env('DB_DATABASE', ''),
                'username' => env('DB_USERNAME', ''),
            ],
        ]);
    }

    private function requirements(): array
    {
        $extension = static fn (string $name): bool => extension_loaded($name);
        $function = static fn (string $name): bool => function_exists($name);

        return [
            ['name' => 'PHP Version', 'current' => PHP_VERSION, 'required' => '8.2+', 'passed' => version_compare(PHP_VERSION, '8.2.0', '>=')],
            ['name' => 'PDO MySQL', 'current' => $extension('pdo_mysql') ? 'On' : 'Off', 'required' => 'On', 'passed' => $extension('pdo_mysql')],
            ['name' => 'Ctype Extension', 'current' => $extension('ctype') ? 'On' : 'Off', 'required' => 'On', 'passed' => $extension('ctype')],
            ['name' => 'Fileinfo Extension', 'current' => $extension('fileinfo') ? 'On' : 'Off', 'required' => 'On', 'passed' => $extension('fileinfo')],
            ['name' => 'Tokenizer Extension', 'current' => $extension('tokenizer') ? 'On' : 'Off', 'required' => 'On', 'passed' => $extension('tokenizer')],
            ['name' => 'JSON Extension', 'current' => $extension('json') ? 'On' : 'Off', 'required' => 'On', 'passed' => $extension('json')],
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
            ['name' => 'Symlink function', 'current' => $function('symlink') ? 'Available' : 'Disabled', 'required' => 'Available', 'passed' => $function('symlink')],
        ];
    }

    public function install(Request $request)
    {
        $setup = session('ashik.install', []);
        $data = array_merge($setup, $request->only(['db_host', 'db_port', 'db_name', 'db_username', 'db_password']));
        $request->merge($data);
        $request->validate([
            'app_name' => ['required', 'string', 'max:120'],
            'app_url' => ['required', 'url', 'max:255'],
            'db_host' => ['required', 'string', 'max:120'],
            'db_port' => ['required', 'integer', 'between:1,65535'],
            'db_name' => ['required', 'string', 'max:120'],
            'db_username' => ['required', 'string', 'max:120'],
            'db_password' => ['nullable', 'string', 'max:255'],
        ]);

        $purchaseCode = strtoupper(trim((string) ($data['purchase_code'] ?? '')));

        if (!collect($this->requirements())->every(fn (array $requirement): bool => $requirement['passed'])) {
            return back()->with('error', 'Please fix all server requirements before installation.');
        }

        InstallLogger::write('Installation started', ['app_url' => $data['app_url']]);

        try {
            $this->testDatabase($data);
            $this->writeDatabaseEnvironment($data);
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('storage:link');
            InstallLogger::write('Migrations and storage link completed');
        } catch (\Throwable $exception) {
            InstallLogger::exception($exception);
            return back()->withInput()->withErrors(['db_name' => 'Database setup failed. Please check the credentials and try again.']);
        }

        if (Schema::hasTable('ashik_installations')) {
            DB::table('ashik_installations')->insert([
                'app_name' => $data['app_name'],
                'app_url' => $data['app_url'],
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
        session()->forget('ashik.install');
        InstallLogger::write('Installation completed successfully');

        $destination = Route::has('login') ? route('login') : url('/');

        return redirect()->to($destination)
            ->with('success', 'Ashik system installed successfully.');
    }

    private function testDatabase(array $data): void
    {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $data['db_host'], $data['db_port'], $data['db_name']);
        $pdo = new \PDO($dsn, $data['db_username'], $data['db_password'] ?? '', [\PDO::ATTR_TIMEOUT => 5]);
        $pdo->query('SELECT 1');
    }

    private function writeDatabaseEnvironment(array $data): void
    {
        $path = base_path('.env');
        if (! is_file($path) || ! is_writable($path)) {
            return;
        }

        $contents = file_get_contents($path);
        foreach (['DB_HOST' => $data['db_host'], 'DB_PORT' => $data['db_port'], 'DB_DATABASE' => $data['db_name'], 'DB_USERNAME' => $data['db_username'], 'DB_PASSWORD' => $data['db_password'] ?? ''] as $key => $value) {
            $line = $key . '=' . (str_contains((string) $value, ' ') ? '"' . $value . '"' : $value);
            if (preg_match('/^' . preg_quote($key, '/') . '=.*/m', $contents)) {
                $contents = preg_replace('/^' . preg_quote($key, '/') . '=.*/m', $line, $contents);
            } else {
                $contents .= PHP_EOL . $line;
            }
        }
        file_put_contents($path, $contents);
        config(['database.connections.mysql.host' => $data['db_host'], 'database.connections.mysql.port' => $data['db_port'], 'database.connections.mysql.database' => $data['db_name'], 'database.connections.mysql.username' => $data['db_username'], 'database.connections.mysql.password' => $data['db_password'] ?? '']);
        DB::purge('mysql');
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
