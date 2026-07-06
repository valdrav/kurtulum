<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('external_api_connections')
            ->where('view_shipment_costs', true)
            ->where('edit_shipment_costs', false)
            ->update(['edit_shipment_costs' => true]);
    }

    public function down(): void
    {
        //
    }
};
