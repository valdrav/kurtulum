<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class DocumentStorageService
{
    protected string $disk = 'local';

    protected string $rootPrefix = 'documents';

    /** @return array{file_count: int, total_bytes: int, total_human: string, orphan_count: int, orphan_bytes: int, orphan_human: string} */
    public function stats(): array
    {
        $totalBytes = (int) Document::query()->sum('size');
        $fileCount = (int) Document::query()->count();
        $orphans = $this->orphanPaths();

        return [
            'file_count' => $fileCount,
            'total_bytes' => $totalBytes,
            'total_human' => $this->humanBytes($totalBytes),
            'orphan_count' => $orphans->count(),
            'orphan_bytes' => (int) $orphans->sum('bytes'),
            'orphan_human' => $this->humanBytes((int) $orphans->sum('bytes')),
        ];
    }

    /** @return Collection<int, array{path: string, bytes: int}> */
    public function orphanPaths(): Collection
    {
        $known = Document::withTrashed()
            ->pluck('path')
            ->filter()
            ->flip();

        $disk = Storage::disk($this->disk);
        $orphans = collect();

        if (! $disk->exists($this->rootPrefix)) {
            return $orphans;
        }

        foreach ($disk->allFiles($this->rootPrefix) as $path) {
            if ($known->has($path)) {
                continue;
            }

            $orphans->push([
                'path' => $path,
                'bytes' => (int) ($disk->size($path) ?: 0),
            ]);
        }

        return $orphans;
    }

    public function purgeOrphans(): int
    {
        $disk = Storage::disk($this->disk);
        $count = 0;

        foreach ($this->orphanPaths() as $orphan) {
            if ($disk->delete($orphan['path'])) {
                $count++;
            }
        }

        $this->pruneEmptyDirectories();

        return $count;
    }

    public function purgeTrashedRecords(): int
    {
        $count = 0;

        Document::onlyTrashed()->chunkById(100, function ($documents) use (&$count) {
            foreach ($documents as $document) {
                $document->purgePhysicalFile();
                $document->forceDelete();
                $count++;
            }
        });

        return $count;
    }

    public function purgePhysicalFile(?string $path, ?string $disk = null): bool
    {
        if (! $path) {
            return false;
        }

        return Storage::disk($disk ?: $this->disk)->delete($path);
    }

    public function humanBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2, ',', '.') . ' GB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1, ',', '.') . ' KB';
        }

        return $bytes . ' B';
    }

    public function isBlockedExtension(string $extension): bool
    {
        $ext = strtolower(ltrim($extension, '.'));

        if ($ext === '') {
            return false;
        }

        return in_array($ext, $this->blockedExtensions(), true);
    }

    /** @return list<string> */
    public function blockedExtensions(): array
    {
        return [
            'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar',
            'exe', 'msi', 'bat', 'cmd', 'com', 'scr', 'vbs', 'js', 'jse',
            'sh', 'bash', 'cgi', 'pl', 'py', 'rb', 'asp', 'aspx', 'jsp',
            'htaccess', 'htpasswd', 'dll', 'so', 'dmg', 'app', 'deb', 'rpm',
        ];
    }

    protected function pruneEmptyDirectories(): void
    {
        $disk = Storage::disk($this->disk);
        $root = storage_path('app/private/' . $this->rootPrefix);

        if (! is_dir($root)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            }
        }
    }
}
