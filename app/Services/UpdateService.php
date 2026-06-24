<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpdateService
{
    public function getCurrentVersion(): string
    {
        return config('ticari.version', '1.0.0');
    }

    public function checkForUpdates(): array
    {
        return Cache::remember('system_update_check', 3600, function () {
            try {
                $response = Http::timeout(10)->get(config('ticari.update.check_url'), [
                    'version' => $this->getCurrentVersion(),
                    'php' => PHP_VERSION,
                ]);

                if ($response->successful()) {
                    return $response->json();
                }
            } catch (\Exception $e) {
                Log::warning('Update check failed: ' . $e->getMessage());
            }

            return [
                'available' => false,
                'current' => $this->getCurrentVersion(),
                'latest' => $this->getCurrentVersion(),
                'message' => __('updates.check_failed'),
            ];
        });
    }

    public function applyUpdate(string $packagePath): array
    {
        if (!File::exists($packagePath)) {
            return ['success' => false, 'message' => __('updates.package_not_found')];
        }

        $backupPath = storage_path('backups/' . date('Y-m-d_His'));
        File::makeDirectory($backupPath, 0755, true);

        $dirsToBackup = ['app', 'config', 'database', 'resources', 'routes'];
        foreach ($dirsToBackup as $dir) {
            if (File::isDirectory(base_path($dir))) {
                File::copyDirectory(base_path($dir), $backupPath . '/' . $dir);
            }
        }

        $zip = new \ZipArchive();
        if ($zip->open($packagePath) !== true) {
            return ['success' => false, 'message' => __('updates.invalid_package')];
        }

        $zip->extractTo(base_path());
        $zip->close();

        if (File::exists(base_path('upgrade.php'))) {
            include base_path('upgrade.php');
            File::delete(base_path('upgrade.php'));
        }

        Cache::forget('system_update_check');

        return ['success' => true, 'message' => __('updates.success')];
    }
}
