<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ports', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('type');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->string('origin')->nullable()->after('destination_port_id');
            $table->string('destination')->nullable()->after('origin');
            $table->foreignId('vessel_id')->nullable()->after('awb_number')->constrained()->nullOnDelete();
            $table->string('voyage_number')->nullable()->after('vessel_id');
            $table->string('cmr_number')->nullable()->after('voyage_number');
            $table->string('flight_number')->nullable()->after('cmr_number');
            $table->string('airline')->nullable()->after('flight_number');
            $table->foreignId('vehicle_id')->nullable()->after('airline')->constrained()->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->after('vehicle_id')->constrained()->nullOnDelete();
            $table->string('carrier')->nullable()->after('driver_id');
            $table->string('forwarder')->nullable()->after('carrier');
            $table->string('currency', 3)->default('USD')->after('forwarder');
            $table->decimal('total_weight_kg', 12, 3)->nullable()->after('currency');
            $table->decimal('total_volume_cbm', 12, 3)->nullable()->after('total_weight_kg');
            $table->unsignedInteger('package_count')->nullable()->after('total_volume_cbm');
            $table->text('cargo_description')->nullable()->after('package_count');
            $table->decimal('total_cost', 15, 2)->default(0)->after('cargo_description');
            $table->foreignId('created_by')->nullable()->after('assigned_user_id')->constrained('users')->nullOnDelete();
        });

        Schema::create('vessel_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vessel_id')->constrained()->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('speed', 8, 2)->nullable();
            $table->decimal('course', 8, 2)->nullable();
            $table->string('source')->default('manual');
            $table->timestamp('recorded_at');
            $table->timestamps();
            $table->index(['vessel_id', 'recorded_at']);
        });

        Schema::table('system_currencies', function (Blueprint $table) {
            $table->timestamp('rate_updated_at')->nullable()->after('exchange_rate');
        });
    }

    public function down(): void
    {
        Schema::table('system_currencies', function (Blueprint $table) {
            $table->dropColumn('rate_updated_at');
        });
        Schema::dropIfExists('vessel_positions');
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vessel_id');
            $table->dropConstrainedForeignId('vehicle_id');
            $table->dropConstrainedForeignId('driver_id');
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn([
                'origin', 'destination', 'voyage_number', 'cmr_number', 'flight_number',
                'airline', 'carrier', 'forwarder', 'currency', 'total_weight_kg',
                'total_volume_cbm', 'package_count', 'cargo_description', 'total_cost',
            ]);
        });
        Schema::table('ports', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
