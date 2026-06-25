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
        foreach ($this->binaryCandidates as $candidate) {
            if (str_contains($candidate, '\\') || str_contains($candidate, '/')) {
                if (is_executable($candidate)) {
                    return $candidate;
                }
                continue;
            }

            $result = Process::run([PHP_OS_FAMILY === 'Windows' ? 'where' : 'which', $candidate]);
            if ($result->successful() && trim($result->output()) !== '') {
                return trim(explode("\n", $result->output())[0]);
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
}
