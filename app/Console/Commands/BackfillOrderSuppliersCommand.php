<?php

namespace App\Console\Commands;

use App\Services\SupplierProfileService;
use Illuminate\Console\Command;

class BackfillOrderSuppliersCommand extends Command
{
    protected $signature = 'orders:backfill-suppliers {--dry-run : Değişiklik yapmadan raporla}';

    protected $description = 'Alış tutarı olan siparişlere ödeme/cari kaydından tedarikçi atar';

    public function handle(SupplierProfileService $profile): int
    {
        if ($this->option('dry-run')) {
            $count = \App\Models\Order::query()
                ->whereNull('supplier_id')
                ->where('purchase_total', '>', 0)
                ->whereNotIn('status', ['cancelled'])
                ->count();

            $this->info("Tedarikçi atanmamış {$count} sipariş bulundu.");
            $this->comment('Gerçek atama için --dry-run olmadan çalıştırın: php artisan orders:backfill-suppliers');

            return self::SUCCESS;
        }

        $updated = $profile->backfillAllMissingSuppliers();
        $this->info("{$updated} siparişe tedarikçi atandı.");

        return self::SUCCESS;
    }
}
