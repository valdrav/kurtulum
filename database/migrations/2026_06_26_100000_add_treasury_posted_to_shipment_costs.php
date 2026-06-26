<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipment_costs', function (Blueprint $table) {
            if (! Schema::hasColumn('shipment_costs', 'treasury_posted_at')) {
                $table->timestamp('treasury_posted_at')->nullable()->after('paid_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipment_costs', function (Blueprint $table) {
            if (Schema::hasColumn('shipment_costs', 'treasury_posted_at')) {
                $table->dropColumn('treasury_posted_at');
            }
        });
    }
};
