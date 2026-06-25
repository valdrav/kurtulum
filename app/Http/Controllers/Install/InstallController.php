<?php

namespace App\Http\Controllers\Install;

use App\Http\Controllers\Controller;
use App\Services\InstallerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class InstallController extends Controller
{
    public function __construct(protected InstallerService $installer) {}

    public function welcome()
    {
        return view('install.welcome');
    }

    public function requirements()
    {
        $requirements = $this->installer->checkRequirements();
        $passed = $this->installer->allRequirementsPassed();

        return view('install.requirements', compact('requirements', 'passed'));
    }

    public function database()
    {
        return view('install.database');
    }

    public function databaseStore(Request $request)
    {
        $driver = $request->input('db_driver', 'sqlite');

        $rules = [
            'app_name' => 'required|string|max:255',
            'app_url' => 'required|url',
            'db_driver' => 'required|in:sqlite,mysql',
        ];

        if ($driver === 'mysql') {
            $rules['db_host'] = 'required|string';
            $rules['db_port'] = 'nullable|string';
            $rules['db_database'] = 'required|string';
            $rules['db_username'] = 'required|string';
            $rules['db_password'] = 'nullable|string';
        }

        $validated = $request->validate($rules);
        $validated['db_driver'] = $driver;

        if ($driver === 'sqlite') {
            $validated['db_database'] = database_path('database.sqlite');
        }

        try {
            $this->installer->testDatabaseConnection($validated);
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['db_connection' => __('install.db_connection_failed') . ': ' . $e->getMessage()]);
        }

        $this->installer->writeEnvFile($validated);

        Artisan::call('config:clear');

        if (empty(config('app.key'))) {
            Artisan::call('key:generate', ['--force' => true]);
        }

        session(['install.db' => $validated]);

        return redirect()->route('install.admin');
    }

    public function admin()
    {
        if (!session('install.db')) {
            return redirect()->route('install.database');
        }

        return view('install.admin');
    }

    public function adminStore(Request $request)
    {
        if (! session('install.db')) {
            return redirect()->route('install.database')
                ->withErrors(['install' => 'Veritabanı adımı oturumu kayboldu. Lütfen tekrar deneyin.']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'locale' => 'required|in:tr,en,ar',
        ]);

        try {
            Artisan::call('optimize:clear', ['--no-interaction' => true]);
            $this->installer->assertVendorReady();
            $this->installer->runMigrations();
            $this->installer->seedRolesAndPermissions();
            $this->installer->createAdmin($validated);
            $this->installer->markAsInstalled();
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->withErrors([
                'install' => __('install.install_failed') . ': ' . $e->getMessage(),
            ]);
        }

        session()->forget('install');

        return redirect()->route('install.complete');
    }

    public function complete()
    {
        return view('install.complete');
    }
}
