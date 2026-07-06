<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_api_connections', function (Blueprint $table) {
            $table->boolean('edit_orders')->default(false)->after('view_orders');
            $table->boolean('edit_shipment_costs')->default(false)->after('view_shipment_costs');
        });
    }

    public function down(): void
    {
        Schema::table('external_api_connections', function (Blueprint $table) {
            $table->dropColumn(['edit_orders', 'edit_shipment_costs']);
        });
    }
};
