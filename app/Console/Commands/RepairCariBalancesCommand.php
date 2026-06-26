<?php

namespace App\Console\Commands;

use App\Models\Account;
use Illuminate\Console\Command;

class RepairCariBalancesCommand extends Command
{
    protected $signature = 'finance:repair-cari-balances {--account= : Account ID or UUID}';

    protected $description = 'Cari hesap bakiyelerini hareket kayıtlarından yeniden hesaplar';

    public function handle(): int
    {
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

        $this->info($fixed > 0 ? "{$fixed} cari hesap düzeltildi." : 'Tüm cari bakiyeler tutarlı.');

        return self::SUCCESS;
    }
}
