<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('income_expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('income_expenses', 'exchange_rate')) {
                $table->decimal('exchange_rate', 15, 6)->default(1)->after('currency');
            }
            if (! Schema::hasColumn('income_expenses', 'amount_base')) {
                $table->decimal('amount_base', 15, 2)->nullable()->after('amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('income_expenses', function (Blueprint $table) {
            $table->dropColumn(['exchange_rate', 'amount_base']);
        });
    }
};
