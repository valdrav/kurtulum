<?php

namespace App\Console\Commands;

use App\Services\DocumentStorageService;
use Illuminate\Console\Command;

class PurgeDocumentOrphansCommand extends Command
{
    protected $signature = 'documents:purge-orphans {--trashed : Also remove soft-deleted document records}';

    protected $description = 'Delete document files on disk that are not linked to any database record';

    public function handle(DocumentStorageService $storage): int
    {
        $orphans = $storage->orphanPaths();
        $this->info('Orphan files found: ' . $orphans->count() . ' (' . $storage->humanBytes((int) $orphans->sum('bytes')) . ')');

        $deleted = $storage->purgeOrphans();
        $this->info("Deleted {$deleted} orphan file(s).");

        if ($this->option('trashed')) {
            $trashed = $storage->purgeTrashedRecords();
            $this->info("Purged {$trashed} soft-deleted document record(s).");
        }

        return self::SUCCESS;
    }
}
