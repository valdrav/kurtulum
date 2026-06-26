<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Services\OrderFinanceService;
use Illuminate\Console\Command;

class RepairCariBalancesCommand extends Command
{
    protected $signature = 'finance:repair-cari-balances
                            {--account= : Belirli cari hesap ID veya UUID}
                            {--skip-deleted-orders : Silinen sipariş düzeltmesini atla}';

    protected $description = 'Silinen siparişlerden kalan cari etkisini sıfırlar ve bakiyeleri hareket kayıtlarından yeniden hesaplar';

    public function handle(OrderFinanceService $finance): int
    {
        if (! $this->option('skip-deleted-orders')) {
            $ordersFixed = $finance->repairDeletedOrdersCari();
            if ($ordersFixed > 0) {
                $this->info("{$ordersFixed} silinen sipariş için cari kayıtlar düzeltildi.");
            }
        }

        $query = Account::query()->cari();

        if ($id = $this->option('account')) {
            $query->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('uuid', $id);
            });
        }

        $fixed = 0;

        $query->each(function (Account $account) use (&$fixed) {
            $opening = (float) $account->opening_balance;
            $delta = (float) $account->transactions()
                ->get()
                ->sum(fn ($tx) => $tx->type === 'credit' ? (float) $tx->amount : -(float) $tx->amount);

            $expected = round($opening + $delta, 2);
            $current = round((float) $account->current_balance, 2);

            if (abs($expected - $current) > 0.01) {
                $account->update(['current_balance' => $expected]);
                $this->line("Düzeltildi: {$account->code} {$account->name} — {$current} → {$expected}");
                $fixed++;
            }
        });

        if ($fixed > 0) {
            $this->info("{$fixed} cari hesap bakiyesi güncellendi.");
        } elseif (! $this->option('skip-deleted-orders')) {
            $this->info('Tüm cari bakiyeler tutarlı.');
        }

        return self::SUCCESS;
    }
}
