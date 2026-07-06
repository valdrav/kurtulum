<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Eski bağlantılarda migration varsayılanları (false) yüzünden sipariş/sevkiyat kapalı kalabiliyordu.
        DB::table('external_api_connections')
            ->where('view_orders', false)
            ->where('view_shipments', false)
            ->where('view_shipment_costs', false)
            ->update([
                'view_orders' => true,
                'view_shipments' => true,
                'view_shipment_costs' => true,
            ]);
    }

    public function down(): void
    {
        // Geri alınmaz — izinler elle düzenlenebilir.
    }
};
