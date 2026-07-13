<?php

namespace App\Console\Commands;

use App\Services\ContentTranslationService;
use App\Services\LangSyncService;
use Illuminate\Console\Command;

class SyncLangCommand extends Command
{
    protected $signature = 'lang:sync
        {--locale=* : Target locales (default: en,fr,de,ar)}
        {--master=tr : Source locale for key structure}
        {--translate : Use AI to translate missing strings from master locale}
        {--refresh-copies : Replace values still identical to master with fallback locale}
        {--dry-run : Show stats without writing files}';

    protected $description = 'Sync translation files: merge missing keys and fill from English fallback for fr/de/ar';

    public function handle(LangSyncService $sync, ContentTranslationService $translator): int
    {
        $locales = $this->option('locale') ?: ['en', 'fr', 'de', 'ar'];
        $master = (string) $this->option('master');
        $dryRun = (bool) $this->option('dry-run');
        $useTranslate = (bool) $this->option('translate');
        $refreshCopies = (bool) $this->option('refresh-copies');

        if ($useTranslate && ! $translator->isEnabled()) {
            $this->warn('AI translation is not configured. Proceeding with merge/fallback only.');
            $useTranslate = false;
        }

        foreach ($locales as $locale) {
            $fillFrom = match ($locale) {
                'en' => null,
                default => 'en',
            };

            $this->info("Syncing [{$locale}] from [{$master}]".($fillFrom ? " (fallback: {$fillFrom})" : ''));

            if ($dryRun) {
                $this->comment('Dry run — no files written.');
                continue;
            }

            $translateFn = null;
            if ($useTranslate) {
                $translateFn = fn (string $text, string $path) => $translator->translate($text, $locale, $master);
            }

            $result = $sync->syncLocale($locale, $master, $fillFrom, $translateFn, $refreshCopies);
            $refreshed = $result['refreshed'] ?? 0;
            $this->line("  → {$result['files']} files, ~{$result['filled']} keys filled, ~{$refreshed} refreshed from fallback");
        }

        $this->info('Done. Clear cache: php artisan optimize:clear');

        return self::SUCCESS;
    }
}
