<?php

namespace App\Console\Commands;

use App\Models\EmailAccount;
use App\Services\ImapMailService;
use Illuminate\Console\Command;

class SyncEmailsCommand extends Command
{
    protected $signature = 'emails:sync {--user= : Belirli kullanıcı ID}';

    protected $description = 'IMAP üzerinden gelen kutularını senkronize eder';

    public function handle(ImapMailService $imap): int
    {
        if (! $imap->isAvailable()) {
            $this->error('PHP IMAP eklentisi yüklü değil.');

            return self::FAILURE;
        }

        $query = EmailAccount::where('is_active', true);
        if ($this->option('user')) {
            $query->where('user_id', $this->option('user'));
        }

        $total = 0;
        foreach ($query->get() as $account) {
            try {
                $count = $imap->syncAccount($account);
                $total += $count;
                $this->info("{$account->email}: {$count} yeni mesaj");
            } catch (\Throwable $e) {
                $this->warn("{$account->email}: {$e->getMessage()}");
            }
        }

        $this->info("Toplam {$total} mesaj senkronize edildi.");

        return self::SUCCESS;
    }
}
