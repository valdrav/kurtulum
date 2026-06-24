<?php

namespace App\Console\Commands;

use App\Services\ExchangeRateService;
use Illuminate\Console\Command;

class SyncExchangeRatesCommand extends Command
{
    protected $signature = 'rates:sync {--force : Cache süresini yoksay}';

    protected $description = 'TCMB / Frankfurter üzerinden döviz kurlarını günceller';

    public function handle(ExchangeRateService $rates): int
    {
        try {
            $result = $rates->sync($this->option('force'));
            $this->info("Güncellenen kur: {$result['updated']}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
