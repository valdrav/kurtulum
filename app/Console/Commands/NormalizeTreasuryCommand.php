<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\Collection;
use App\Models\Payment;
use App\Services\CompanyTreasuryService;
use Illuminate\Console\Command;

class NormalizeTreasuryCommand extends Command
{
    protected $signature = 'finance:normalize-treasury {--dry-run : Show changes without writing}';

    protected $description = 'Fix treasury account flags and metadata without deleting financial records';

    public function handle(CompanyTreasuryService $treasury): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Dry run — no database changes will be saved.');
        }

        $treasury->ensureDefaults();
        $default = $treasury->defaultAccount();

        $misclassified = Account::query()
            ->where('is_treasury', true)
            ->where(function ($query) {
                $query->whereNotNull('customer_id')
                    ->orWhereNotNull('supplier_id')
                    ->orWhereIn('type', ['customer', 'supplier']);
            })
            ->get();

        foreach ($misclassified as $account) {
            $this->line("Cari hesaptan treasury bayrağı kaldırılıyor: {$account->code} {$account->name}");
            if (! $dryRun) {
                $account->update(['is_treasury' => false]);
            }
        }

        $legacyCash = Account::query()
            ->companyTreasury()
            ->where('type', 'cash')
            ->get();

        foreach ($legacyCash as $account) {
            $this->line("Nakit hesap banka olarak etiketleniyor: {$account->code} {$account->name}");
            if (! $dryRun) {
                $account->update(['type' => 'bank']);
            }
        }

        $currencyFixes = AccountTransaction::query()
            ->whereIn('account_id', Account::query()->companyTreasury()->select('id'))
            ->whereHas('account', fn ($q) => $q->whereColumn('accounts.currency', '!=', 'account_transactions.currency'))
            ->with('account:id,currency')
            ->get();

        foreach ($currencyFixes as $transaction) {
            $this->line("İşlem para birimi düzeltiliyor #{$transaction->id}: {$transaction->currency} → {$transaction->account?->currency}");
            if (! $dryRun && $transaction->account) {
                $transaction->update(['currency' => $transaction->account->currency]);
            }
        }

        $nullTreasuryCollections = Collection::query()->whereNull('treasury_account_id')->count();
        $nullTreasuryPayments = Payment::query()->whereNull('treasury_account_id')->count();

        if ($nullTreasuryCollections > 0) {
            $this->line("{$nullTreasuryCollections} tahsilata varsayılan banka hesabı atanacak.");
            if (! $dryRun) {
                Collection::query()->whereNull('treasury_account_id')->update(['treasury_account_id' => $default->id]);
            }
        }

        if ($nullTreasuryPayments > 0) {
            $this->line("{$nullTreasuryPayments} ödemeye varsayılan banka hesabı atanacak.");
            if (! $dryRun) {
                Payment::query()->whereNull('treasury_account_id')->update(['treasury_account_id' => $default->id]);
            }
        }

        if (! $dryRun) {
            $this->info('Changes saved.');
        }

        $this->info('Treasury normalization complete. Run finance:repair-treasury-balances to verify balances.');

        return self::SUCCESS;
    }
}
