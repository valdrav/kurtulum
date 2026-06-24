<?php

namespace App\Services;

use App\Core\ModuleManager;
use App\Models\SystemModule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class ModuleInstallService
{
    public function __construct(protected ModuleManager $modules) {}

    public function installFromUpload(UploadedFile $file): SystemModule
    {
        $this->assertZipExtension();

        $tempDir = storage_path('app/temp/modules/' . Str::uuid());
        File::ensureDirectoryExists($tempDir);

        try {
            $zipFilename = Str::uuid() . '.zip';
            $fullZipPath = storage_path('app/temp/modules/' . $zipFilename);
            File::ensureDirectoryExists(dirname($fullZipPath));
            File::copy($file->getRealPath(), $fullZipPath);

            $root = $this->extractAndResolveRoot($fullZipPath, $tempDir);
            $manifest = $this->readManifest($root);
            $slug = $this->normalizeSlug($manifest['slug']);
            $targetDir = $this->resolveTargetDirectory($manifest, $root);

            if (SystemModule::where('slug', $slug)->where('is_core', true)->exists()) {
                throw ValidationException::withMessages([
                    'module_file' => [__('extensions.module_core_conflict', ['slug' => $slug])],
                ]);
            }

            if (File::isDirectory($targetDir)) {
                if (SystemModule::where('slug', $slug)->where('is_enabled', true)->exists()) {
                    throw ValidationException::withMessages([
                        'module_file' => [__('extensions.module_active_overwrite', ['slug' => $slug])],
                    ]);
                }

                File::deleteDirectory($targetDir);
            }

            File::ensureDirectoryExists(dirname($targetDir));
            File::moveDirectory($root, $targetDir);

            $this->modules->syncDiscovered();

            $module = SystemModule::where('slug', $slug)->first();

            if (! $module) {
                throw ValidationException::withMessages([
                    'module_file' => [__('extensions.module_sync_failed')],
                ]);
            }

            return $module;
        } finally {
            if (File::isDirectory($tempDir)) {
                File::deleteDirectory($tempDir);
            }

            if (isset($fullZipPath) && File::exists($fullZipPath)) {
                File::delete($fullZipPath);
            }
        }
    }

    public function remove(SystemModule $module): void
    {
        if ($module->is_core) {
            throw ValidationException::withMessages([
                'module' => [__('extensions.core_module_cannot_disable')],
            ]);
        }

        if ($module->is_enabled) {
            $this->modules->disable($module);
        }

        if ($module->path && File::isDirectory($module->path)) {
            File::deleteDirectory($module->path);
        }

        $module->delete();
        cache()->forget('system.menu.items');
    }

    protected function assertZipExtension(): void
    {
        if (class_exists(ZipArchive::class)) {
            return;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            return;
        }

        throw ValidationException::withMessages([
            'module_file' => [__('extensions.zip_extension_missing')],
        ]);
    }

    protected function extractArchive(string $zipPath, string $dest): void
    {
        if (class_exists(ZipArchive::class)) {
            $zip = new ZipArchive();

            if ($zip->open($zipPath) !== true) {
                throw ValidationException::withMessages([
                    'module_file' => [__('extensions.module_invalid_zip')],
                ]);
            }

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if ($this->isUnsafeZipPath($name)) {
                    $zip->close();
                    throw ValidationException::withMessages([
                        'module_file' => [__('extensions.module_unsafe_zip')],
                    ]);
                }
            }

            if (! $zip->extractTo($dest)) {
                $zip->close();
                throw ValidationException::withMessages([
                    'module_file' => [__('extensions.module_extract_failed')],
                ]);
            }

            $zip->close();

            return;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            if (! File::exists($zipPath)) {
                throw ValidationException::withMessages([
                    'module_file' => [__('extensions.module_invalid_zip')],
                ]);
            }

            File::ensureDirectoryExists($dest);
            $zipWin = str_replace('/', '\\', $zipPath);
            $destWin = str_replace('/', '\\', $dest);
            $command = sprintf(
                'powershell -NoProfile -Command "Expand-Archive -LiteralPath \'%s\' -DestinationPath \'%s\' -Force"',
                $zipWin,
                $destWin
            );
            exec($command, $output, $code);

            if ($code !== 0) {
                throw ValidationException::withMessages([
                    'module_file' => [__('extensions.module_extract_failed')],
                ]);
            }

            return;
        }

        throw ValidationException::withMessages([
            'module_file' => [__('extensions.zip_extension_missing')],
        ]);
    }

    protected function extractAndResolveRoot(string $zipPath, string $tempDir): string
    {
        $this->extractArchive($zipPath, $tempDir);

        $manifestPaths = $this->findManifestPaths($tempDir);

        if ($manifestPaths === []) {
            throw ValidationException::withMessages([
                'module_file' => [__('extensions.module_manifest_missing')],
            ]);
        }

        if (count($manifestPaths) > 1) {
            throw ValidationException::withMessages([
                'module_file' => [__('extensions.module_multiple_manifests')],
            ]);
        }

        return dirname($manifestPaths[0]);
    }

    protected function findManifestPaths(string $baseDir): array
    {
        $paths = [];

        foreach (File::allFiles($baseDir) as $file) {
            if ($file->getFilename() === 'module.json') {
                $paths[] = $file->getPathname();
            }
        }

        return $paths;
    }

    protected function readManifest(string $root): array
    {
        $manifestPath = $root . DIRECTORY_SEPARATOR . 'module.json';

        if (! File::exists($manifestPath)) {
            throw ValidationException::withMessages([
                'module_file' => [__('extensions.module_manifest_missing')],
            ]);
        }

        $manifest = json_decode($this->stripBom(File::get($manifestPath)), true);

        if (! is_array($manifest) || empty($manifest['slug']) || empty($manifest['name'])) {
            throw ValidationException::withMessages([
                'module_file' => [__('extensions.module_manifest_invalid')],
            ]);
        }

        $provider = $manifest['provider'] ?? null;

        if ($provider) {
            $providerPath = $root . DIRECTORY_SEPARATOR . 'ModuleServiceProvider.php';
            if (! File::exists($providerPath)) {
                throw ValidationException::withMessages([
                    'module_file' => [__('extensions.module_provider_missing')],
                ]);
            }
        }

        return $manifest;
    }

    protected function resolveTargetDirectory(array $manifest, string $root): string
    {
        $slug = $this->normalizeSlug($manifest['slug']);
        $folder = basename(str_replace('\\', '/', $root));

        if (preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $folder)) {
            return base_path('modules/' . $folder);
        }

        $name = Str::studly($slug);

        return base_path('modules/' . $name);
    }

    protected function normalizeSlug(string $slug): string
    {
        $slug = Str::slug($slug);

        if ($slug === '') {
            throw ValidationException::withMessages([
                'module_file' => [__('extensions.module_manifest_invalid')],
            ]);
        }

        return $slug;
    }

    protected function stripBom(string $content): string
    {
        return preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
    }

    protected function isUnsafeZipPath(string $path): bool
    {
        $normalized = str_replace('\\', '/', $path);

        return str_starts_with($normalized, '/')
            || str_contains($normalized, '../')
            || str_contains($normalized, '/..')
            || str_contains($normalized, '..\\');
    }
}
