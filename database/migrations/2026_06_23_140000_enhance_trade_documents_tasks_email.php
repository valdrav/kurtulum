<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            $table->decimal('purchase_total', 15, 2)->default(0)->after('total_amount');
            $table->decimal('sale_total', 15, 2)->default(0)->after('purchase_total');
            $table->decimal('margin_total', 15, 2)->default(0)->after('sale_total');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('purchase_unit_price', 15, 4)->nullable()->after('unit_price');
            $table->decimal('purchase_discount_percent', 5, 2)->default(0)->after('purchase_unit_price');
            $table->decimal('sale_unit_price', 15, 4)->nullable()->after('purchase_discount_percent');
            $table->decimal('purchase_total', 15, 2)->default(0)->after('total');
            $table->decimal('margin_amount', 15, 2)->default(0)->after('purchase_total');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->string('folder')->nullable()->after('category');
            $table->json('tags')->nullable()->after('folder');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->json('checklist')->nullable()->after('description');
            $table->json('labels')->nullable()->after('checklist');
            $table->dateTime('reminder_at')->nullable()->after('due_date');
            $table->unsignedSmallInteger('estimated_hours')->nullable()->after('reminder_at');
        });

        Schema::table('email_accounts', function (Blueprint $table) {
            $table->string('provider')->default('custom')->after('name');
            $table->string('smtp_encryption')->nullable()->after('smtp_port');
            $table->string('imap_encryption')->nullable()->after('imap_port');
        });
    }

    public function down(): void
    {
        Schema::table('email_accounts', function (Blueprint $table) {
            $table->dropColumn(['provider', 'smtp_encryption', 'imap_encryption']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['checklist', 'labels', 'reminder_at', 'estimated_hours']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['folder', 'tags']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn([
                'purchase_unit_price', 'purchase_discount_percent', 'sale_unit_price',
                'purchase_total', 'margin_amount',
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_id');
            $table->dropColumn(['purchase_total', 'sale_total', 'margin_total']);
        });
    }
};
