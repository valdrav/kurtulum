<?php

namespace App\Console\Commands;

use Database\Seeders\DemoDataSeeder;
use Illuminate\Console\Command;

class SeedDemoCommand extends Command
{
    protected $signature = 'ticari:demo-seed';

    protected $description = 'ExportFlow demo verilerini yükler (müşteri, sipariş, personel, firma ayarları)';

    public function handle(): int
    {
        $this->info('Demo veriler yükleniyor...');
        $this->call('db:seed', ['--class' => DemoDataSeeder::class, '--force' => true]);
        $this->call('config:clear');
        $this->info('Tamamlandı! Demo kullanıcılar: manager@exportflow.demo / demo1234');

        return self::SUCCESS;
    }
}
