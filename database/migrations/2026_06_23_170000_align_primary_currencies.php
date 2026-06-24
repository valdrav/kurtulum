<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('system_currencies')) {
            return;
        }

        $names = [
            'TRY' => 'Türk Lirası',
            'USD' => 'ABD Doları',
            'EUR' => 'Euro',
            'SAR' => 'Suudi Riyali',
            'GBP' => 'İngiliz Sterlini',
            'AED' => 'BAE Dirhemi',
            'CNY' => 'Çin Yuanı',
            'RUB' => 'Rus Rublesi',
        ];

        foreach ($names as $code => $name) {
            DB::table('system_currencies')->where('code', $code)->update(['name' => $name]);
        }

        foreach (['GBP', 'AED', 'CNY', 'RUB'] as $code) {
            DB::table('system_currencies')->where('code', $code)->update(['is_active' => false]);
        }

        $order = ['TRY' => 1, 'USD' => 2, 'EUR' => 3, 'SAR' => 4];
        foreach ($order as $code => $sort) {
            DB::table('system_currencies')->where('code', $code)->update(['sort_order' => $sort, 'is_active' => true]);
        }
    }

    public function down(): void
    {
        //
    }
};
