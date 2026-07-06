<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_api_connections', function (Blueprint $table) {
            if (! Schema::hasColumn('external_api_connections', 'edit_shipments')) {
                $table->boolean('edit_shipments')->default(false)->after('view_shipments');
            }
        });
    }

    public function down(): void
    {
        Schema::table('external_api_connections', function (Blueprint $table) {
            if (Schema::hasColumn('external_api_connections', 'edit_shipments')) {
                $table->dropColumn('edit_shipments');
            }
        });
    }
};
