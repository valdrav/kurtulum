<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ports', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code')->unique()->comment('Liman/ havalimanı kodu');
            $table->string('name');
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('type')->default('sea')->comment('sea, air, rail, road');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('vessels', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('imo_number')->nullable()->unique();
            $table->string('mmsi')->nullable();
            $table->string('flag_country')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('plate_number')->unique();
            $table->string('type')->nullable()->comment('truck, trailer, van');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->decimal('capacity', 12, 2)->nullable()->comment('Kapasite (ton/m³)');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('license_number')->nullable();
            $table->date('license_expiry')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('containers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('container_number')->unique()->comment('Konteyner numarası');
            $table->string('type')->default('20GP')->comment('20GP, 40HC, vb.');
            $table->string('seal_number')->nullable();
            $table->decimal('tare_weight', 10, 2)->nullable();
            $table->decimal('max_payload', 10, 2)->nullable();
            $table->string('status')->default('available')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('shipment_number')->unique()->comment('Sevkiyat numarası');
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('transport_mode', ['road', 'sea', 'air', 'rail', 'multimodal'])->default('sea');
            $table->string('status')->default('planned')->index();
            $table->foreignId('origin_port_id')->nullable()->constrained('ports')->nullOnDelete();
            $table->foreignId('destination_port_id')->nullable()->constrained('ports')->nullOnDelete();
            $table->date('etd')->nullable()->comment('Tahmini kalkış');
            $table->date('eta')->nullable()->comment('Tahmini varış');
            $table->date('atd')->nullable()->comment('Gerçek kalkış');
            $table->date('ata')->nullable()->comment('Gerçek varış');
            $table->string('bl_number')->nullable()->comment('Konşimento numarası');
            $table->string('awb_number')->nullable()->comment('Hava konşimentosu numarası');
            $table->string('incoterm')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('shipment_legs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence')->default(1);
            $table->enum('transport_mode', ['road', 'sea', 'air', 'rail', 'multimodal']);
            $table->string('origin')->nullable();
            $table->string('destination')->nullable();
            $table->foreignId('origin_port_id')->nullable()->constrained('ports')->nullOnDelete();
            $table->foreignId('destination_port_id')->nullable()->constrained('ports')->nullOnDelete();
            $table->foreignId('vessel_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->date('etd')->nullable();
            $table->date('eta')->nullable();
            $table->date('atd')->nullable();
            $table->date('ata')->nullable();
            $table->string('carrier_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('shipment_container', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('container_id')->constrained()->cascadeOnDelete();
            $table->unique(['shipment_id', 'container_id']);
        });

        Schema::create('shipment_costs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('type')->comment('freight, insurance, customs, handling, vb.');
            $table->string('description')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number')->nullable();
            $table->date('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('shipment_milestones', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamp('expected_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('location')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customs_declarations', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('declaration_number')->unique();
            $table->string('declaration_type')->default('export')->comment('export, import, transit');
            $table->string('status')->default('draft')->index();
            $table->date('declared_at')->nullable();
            $table->date('cleared_at')->nullable();
            $table->string('customs_office')->nullable();
            $table->decimal('total_value', 15, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('letters_of_credit', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('shipment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('lc_number')->unique()->comment('Akreditif numarası');
            $table->string('issuing_bank')->nullable();
            $table->string('advising_bank')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('status')->default('open')->index();
            $table->text('terms')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('letters_of_credit');
        Schema::dropIfExists('customs_declarations');
        Schema::dropIfExists('shipment_milestones');
        Schema::dropIfExists('shipment_costs');
        Schema::dropIfExists('shipment_container');
        Schema::dropIfExists('shipment_legs');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('containers');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('vessels');
        Schema::dropIfExists('ports');
    }
};
