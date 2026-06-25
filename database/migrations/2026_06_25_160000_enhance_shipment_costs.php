<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipment_costs', function (Blueprint $table) {
            if (! Schema::hasColumn('shipment_costs', 'item_name')) {
                $table->string('item_name')->nullable()->after('type');
            }
            if (! Schema::hasColumn('shipment_costs', 'expense_date')) {
                $table->date('expense_date')->nullable()->after('invoice_number');
            }
            if (! Schema::hasColumn('shipment_costs', 'payee')) {
                $table->string('payee')->nullable()->after('description');
            }
            if (! Schema::hasColumn('shipment_costs', 'country')) {
                $table->string('country', 100)->nullable()->after('payee');
            }
            if (! Schema::hasColumn('shipment_costs', 'amount_try')) {
                $table->decimal('amount_try', 15, 2)->nullable()->after('amount');
            }
            if (! Schema::hasColumn('shipment_costs', 'exchange_rate')) {
                $table->decimal('exchange_rate', 15, 6)->nullable()->after('amount_try');
            }
            if (! Schema::hasColumn('shipment_costs', 'notes')) {
                $table->text('notes')->nullable()->after('exchange_rate');
            }
            if (! Schema::hasColumn('shipment_costs', 'status')) {
                $table->string('status', 32)->default('pending')->after('notes')->index();
            }
            if (! Schema::hasColumn('shipment_costs', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('paid_at')->constrained()->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipment_costs', function (Blueprint $table) {
            $columns = ['item_name', 'expense_date', 'payee', 'country', 'amount_try', 'exchange_rate', 'notes', 'status', 'user_id'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('shipment_costs', $column)) {
                    if ($column === 'user_id') {
                        $table->dropConstrainedForeignId('user_id');
                    } else {
                        $table->dropColumn($column);
                    }
                }
            }
        });
    }
};
