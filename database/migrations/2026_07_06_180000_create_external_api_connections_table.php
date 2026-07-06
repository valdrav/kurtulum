<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_api_connections', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('token_prefix', 16);
            $table->char('token_hash', 64);
            $table->boolean('is_active')->default(true);
            $table->boolean('view_customer')->default(true);
            $table->boolean('view_directory')->default(false);
            $table->boolean('view_orders')->default(false);
            $table->boolean('view_shipments')->default(false);
            $table->boolean('view_shipment_costs')->default(false);
            $table->timestamp('last_used_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('token_hash');
            $table->index(['customer_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_api_connections');
    }
};
