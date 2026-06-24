<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vessels', function (Blueprint $table) {
            $table->string('marinetraffic_ship_id')->nullable()->after('flag_country');
            $table->string('vessel_type')->nullable()->after('marinetraffic_ship_id');
            $table->string('callsign')->nullable()->after('vessel_type');
            $table->string('dwt')->nullable()->after('callsign');
            $table->string('mt_url')->nullable()->after('dwt');
        });

        Schema::table('vessel_positions', function (Blueprint $table) {
            $table->json('meta')->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('vessel_positions', function (Blueprint $table) {
            $table->dropColumn('meta');
        });

        Schema::table('vessels', function (Blueprint $table) {
            $table->dropColumn(['marinetraffic_ship_id', 'vessel_type', 'callsign', 'dwt', 'mt_url']);
        });
    }
};
