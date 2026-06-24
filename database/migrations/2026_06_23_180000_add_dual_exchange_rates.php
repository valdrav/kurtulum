<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_currencies', function (Blueprint $table) {
            $table->decimal('tcmb_rate', 16, 6)->nullable()->after('exchange_rate');
            $table->decimal('market_rate', 16, 6)->nullable()->after('tcmb_rate');
        });
    }

    public function down(): void
    {
        Schema::table('system_currencies', function (Blueprint $table) {
            $table->dropColumn(['tcmb_rate', 'market_rate']);
        });
    }
};
