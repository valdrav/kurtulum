<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('income_expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('income_expenses', 'item_name')) {
                $table->string('item_name')->nullable()->after('category');
            }
            if (! Schema::hasColumn('income_expenses', 'vendor')) {
                $table->string('vendor')->nullable()->after('item_name');
            }
            if (! Schema::hasColumn('income_expenses', 'quantity')) {
                $table->decimal('quantity', 15, 4)->nullable()->after('vendor');
            }
            if (! Schema::hasColumn('income_expenses', 'unit')) {
                $table->string('unit', 20)->nullable()->after('quantity');
            }
            if (! Schema::hasColumn('income_expenses', 'unit_price')) {
                $table->decimal('unit_price', 15, 4)->nullable()->after('unit');
            }
            if (! Schema::hasColumn('income_expenses', 'payment_method')) {
                $table->string('payment_method', 50)->nullable()->after('unit_price');
            }
            if (! Schema::hasColumn('income_expenses', 'receipt_no')) {
                $table->string('receipt_no')->nullable()->after('payment_method');
            }
            if (! Schema::hasColumn('income_expenses', 'notes')) {
                $table->text('notes')->nullable()->after('receipt_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('income_expenses', function (Blueprint $table) {
            $table->dropColumn([
                'item_name', 'vendor', 'quantity', 'unit', 'unit_price',
                'payment_method', 'receipt_no', 'notes',
            ]);
        });
    }
};
