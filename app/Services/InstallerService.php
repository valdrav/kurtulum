<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class InstallerService
{
    public function assertVendorReady(): void
    {
        if (! is_file(base_path('vendor/autoload.php'))) {
            throw new \RuntimeException(
                'vendor/ klasörü yok. Cursor\'dan son commit\'i push edip Plesk Git Pull yapın (vendor artık repoda).'
            );
        }

        if (! is_file(base_path('vendor/spatie/laravel-permission/src/Models/Permission.php'))) {
            throw new \RuntimeException(
                'Spatie paketleri eksik. Bilgisayardan push edin — vendor klasörü GitHub\'a dahil edildi.'
            );
        }
    }

    public function checkRequirements(): array
    {
        return [
            'php_version' => [
                'label' => 'PHP 8.2+',
                'passed' => version_compare(PHP_VERSION, '8.2.0', '>='),
                'current' => PHP_VERSION,
            ],
            'pdo' => [
                'label' => 'PDO Extension',
                'passed' => extension_loaded('pdo'),
                'current' => extension_loaded('pdo') ? 'Enabled' : 'Disabled',
            ],
            'pdo_sqlite' => [
                'label' => 'PDO SQLite',
                'passed' => extension_loaded('pdo_sqlite'),
                'current' => extension_loaded('pdo_sqlite') ? 'Enabled' : 'Disabled',
            ],
            'pdo_mysql' => [
                'label' => 'PDO MySQL / MariaDB (Plesk kurulumu için)',
                'passed' => extension_loaded('pdo_mysql'),
                'current' => extension_loaded('pdo_mysql') ? 'Enabled' : 'Disabled',
            ],
            'mbstring' => [
                'label' => 'Mbstring',
                'passed' => extension_loaded('mbstring'),
                'current' => extension_loaded('mbstring') ? 'Enabled' : 'Disabled',
            ],
            'openssl' => [
                'label' => 'OpenSSL',
                'passed' => extension_loaded('openssl'),
                'current' => extension_loaded('openssl') ? 'Enabled' : 'Disabled',
            ],
            'tokenizer' => [
                'label' => 'Tokenizer',
                'passed' => extension_loaded('tokenizer'),
                'current' => extension_loaded('tokenizer') ? 'Enabled' : 'Disabled',
            ],
            'json' => [
                'label' => 'JSON',
                'passed' => extension_loaded('json'),
                'current' => extension_loaded('json') ? 'Enabled' : 'Disabled',
            ],
            'curl' => [
                'label' => 'cURL',
                'passed' => extension_loaded('curl'),
                'current' => extension_loaded('curl') ? 'Enabled' : 'Disabled',
            ],
            'storage_writable' => [
                'label' => 'storage/ Writable',
                'passed' => is_writable(storage_path()),
                'current' => is_writable(storage_path()) ? 'Writable' : 'Not Writable',
            ],
            'bootstrap_cache_writable' => [
                'label' => 'bootstrap/cache/ Writable',
                'passed' => is_writable(base_path('bootstrap/cache')),
                'current' => is_writable(base_path('bootstrap/cache')) ? 'Writable' : 'Not Writable',
            ],
            'composer_vendor' => [
                'label' => 'Composer vendor (Spatie paketleri)',
                'passed' => is_file(base_path('vendor/autoload.php'))
                    && is_file(base_path('vendor/spatie/laravel-permission/src/Models/Permission.php')),
                'current' => is_file(base_path('vendor/spatie/laravel-permission/src/Models/Permission.php'))
                    ? 'OK'
                    : (is_file(base_path('vendor/autoload.php')) ? 'Spatie eksik — Git pull (vendor repoda)' : 'vendor/ yok — Git pull yapin'),
            ],
        ];
    }

    public function allRequirementsPassed(): bool
    {
        $requirements = collect($this->checkRequirements());

        $corePassed = $requirements
            ->except(['pdo_mysql'])
            ->every(fn ($req) => $req['passed']);

        $dbDriverAvailable = $requirements['pdo_sqlite']['passed'] || $requirements['pdo_mysql']['passed'];

        return $corePassed && $dbDriverAvailable;
    }

    public function testDatabaseConnection(array $config): bool
    {
        $driver = $config['db_driver'] ?? 'sqlite';

        if ($driver === 'sqlite') {
            $path = $config['db_database'] ?? database_path('database.sqlite');
            $dir = dirname($path);

            if (!is_dir($dir)) {
                File::makeDirectory($dir, 0755, true);
            }

            if (!file_exists($path)) {
                File::put($path, '');
            }

            config([
                'database.connections.install_test' => [
                    'driver' => 'sqlite',
                    'database' => $path,
                    'foreign_key_constraints' => true,
                ],
            ]);
        } else {
            config([
                'database.connections.install_test' => [
                    'driver' => 'mysql',
                    'host' => $config['db_host'],
                    'port' => $config['db_port'] ?? '3306',
                    'database' => $config['db_database'],
                    'username' => $config['db_username'],
                    'password' => $config['db_password'] ?? '',
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                ],
            ]);
        }

        DB::connection('install_test')->getPdo();
        DB::purge('install_test');

        return true;
    }

    public function writeEnvFile(array $data): void
    {
        $envPath = base_path('.env');
        $env = File::exists($envPath) ? File::get($envPath) : File::get(base_path('.env.example'));

        $driver = $data['db_driver'] ?? 'sqlite';

        $replacements = [
            'APP_NAME' => $data['app_name'] ?? 'ExportFlow ERP',
            'APP_URL' => $data['app_url'] ?? url('/'),
            'DB_CONNECTION' => $driver,
            'APP_INSTALLED' => 'false',
            'SESSION_DRIVER' => 'file',
            'CACHE_STORE' => 'file',
            'QUEUE_CONNECTION' => 'sync',
            'SESSION_DOMAIN' => '',
        ];

        if ($driver === 'sqlite') {
            $replacements['DB_DATABASE'] = $data['db_database'] ?? database_path('database.sqlite');
        } else {
            $replacements['DB_HOST'] = $data['db_host'];
            $replacements['DB_PORT'] = $data['db_port'] ?? '3306';
            $replacements['DB_DATABASE'] = $data['db_database'];
            $replacements['DB_USERNAME'] = $data['db_username'];
            $replacements['DB_PASSWORD'] = $data['db_password'] ?? '';
        }

        foreach ($replacements as $key => $value) {
            $escaped = ($value !== '' && str_contains((string) $value, ' ')) ? '"' . $value . '"' : $value;
            if (preg_match("/^{$key}=.*/m", $env)) {
                $env = preg_replace("/^{$key}=.*/m", "{$key}={$escaped}", $env);
            } else {
                $env .= "\n{$key}={$escaped}";
            }
        }

        if (!str_contains($env, 'APP_KEY=') || str_contains($env, 'APP_KEY=') && preg_match('/APP_KEY=\s*$/m', $env)) {
            // key will be generated
        }

        File::put($envPath, $env);
    }

    public function runMigrations(): void
    {
        Artisan::call('optimize:clear', ['--no-interaction' => true]);
        Artisan::call('migrate', ['--force' => true]);
    }

    public function seedRolesAndPermissions(): void
    {
        Artisan::call('db:seed', ['--class' => 'RolesAndPermissionsSeeder', '--force' => true]);
        Artisan::call('db:seed', ['--class' => 'ReferenceDataSeeder', '--force' => true]);
        Artisan::call('db:seed', ['--class' => 'ExtensibilitySeeder', '--force' => true]);
    }

    public function createAdmin(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'locale' => $data['locale'] ?? 'tr',
            'theme' => 'light',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $user->assignRole('super-admin');

        $this->seedCompanyDefaults($data['name'] ?? config('app.name'), $data['email'] ?? null);

        return $user;
    }

    public function seedCompanyDefaults(string $companyName, ?string $email = null): void
    {
        Setting::set('company_name', $companyName, 'company');
        if ($email) {
            Setting::set('company_email', $email, 'company');
        }
        Setting::set('default_currency', 'USD', 'company');
        Setting::set('timezone', 'Europe/Istanbul', 'company');
    }

    public function markAsInstalled(): void
    {
        $this->updateEnvValue('APP_INSTALLED', 'true');
        $this->updateEnvValue('SESSION_DRIVER', 'database');
        $this->updateEnvValue('CACHE_STORE', 'database');
        $this->updateEnvValue('QUEUE_CONNECTION', 'database');
        Artisan::call('config:clear');
        $this->optimizeProduction();
    }

    public function optimizeProduction(): void
    {
        Artisan::call('optimize', ['--no-interaction' => true]);
    }

    public function updateEnvValue(string $key, string $value): void
    {
        $envPath = base_path('.env');
        $env = File::get($envPath);
        $escaped = str_contains($value, ' ') ? '"' . $value . '"' : $value;

        if (preg_match("/^{$key}=.*/m", $env)) {
            $env = preg_replace("/^{$key}=.*/m", "{$key}={$escaped}", $env);
        } else {
            $env .= "\n{$key}={$escaped}";
        }

        File::put($envPath, $env);
    }
}
