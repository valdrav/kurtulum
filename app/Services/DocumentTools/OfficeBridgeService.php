<?php

namespace App\Services\DocumentTools;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class OfficeBridgeService
{
    /** @var list<string> */
    protected array $binaryCandidates = [
        'soffice',
        'libreoffice',
        'soffice.exe',
        'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
        '/usr/bin/soffice',
        '/usr/bin/libreoffice',
    ];

    public function isAvailable(): bool
    {
        return $this->binary() !== null;
    }

    public function binary(): ?string
    {
        $configured = config('ticari.document_tools.soffice_path');
        if (is_string($configured) && $configured !== '' && $this->isExecutableSafe($configured)) {
            return $configured;
        }

        foreach ($this->binaryCandidates as $candidate) {
            if (! str_contains($candidate, '\\') && ! str_contains($candidate, '/')) {
                $resolved = $this->resolveViaWhich($candidate);
                if ($resolved !== null) {
                    return $resolved;
                }
                continue;
            }

            if ($this->isExecutableSafe($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    public function convert(string $inputPath, string $targetFormat, string $outputDir): ?string
    {
        $binary = $this->binary();
        if (! $binary) {
            return null;
        }

        $profile = $outputDir . '/lo-profile-' . Str::uuid();
        mkdir($profile, 0755, true);

        $result = Process::timeout(120)->run([
            $binary,
            '--headless',
            '-env:UserInstallation=file:///' . str_replace('\\', '/', $profile),
            '--convert-to',
            $targetFormat,
            '--outdir',
            $outputDir,
            $inputPath,
        ]);

        if (! $result->successful()) {
            return null;
        }

        $base = pathinfo($inputPath, PATHINFO_FILENAME);
        $candidate = $outputDir . '/' . $base . '.' . $targetFormat;

        return is_file($candidate) ? $candidate : null;
    }

    protected function resolveViaWhich(string $command): ?string
    {
        try {
            $result = Process::run([PHP_OS_FAMILY === 'Windows' ? 'where' : 'which', $command]);
            if ($result->successful() && trim($result->output()) !== '') {
                $path = trim(explode("\n", $result->output())[0]);

                return $this->isExecutableSafe($path) ? $path : null;
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    protected function isExecutableSafe(string $path): bool
    {
        if (! $this->isPathAllowed($path)) {
            return false;
        }

        try {
            return is_file($path) && is_executable($path);
        } catch (\Throwable) {
            return false;
        }
    }

    protected function isPathAllowed(string $path): bool
    {
        $openBasedir = ini_get('open_basedir');
        if (! is_string($openBasedir) || $openBasedir === '') {
            return true;
        }

        $normalized = str_replace('\\', '/', $path);

        foreach (preg_split('/[:;]/', $openBasedir) ?: [] as $allowed) {
            $allowed = rtrim(str_replace('\\', '/', trim($allowed)), '/');
            if ($allowed === '') {
                continue;
            }
            if (str_starts_with($normalized, $allowed)) {
                return true;
            }
        }

        return false;
    }
}
