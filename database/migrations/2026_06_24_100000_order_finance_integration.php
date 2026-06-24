<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('amount_collected', 15, 2)->default(0)->after('margin_total');
            $table->decimal('amount_paid', 15, 2)->default(0)->after('amount_collected');
            $table->timestamp('finance_posted_at')->nullable()->after('amount_paid');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('supplier_id')->constrained()->nullOnDelete();
            $table->foreignId('treasury_account_id')->nullable()->after('order_id')->constrained('accounts')->nullOnDelete();
        });

        Schema::table('collections', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            $table->foreignId('treasury_account_id')->nullable()->after('order_id')->constrained('accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropConstrainedForeignId('treasury_account_id');
            $table->dropConstrainedForeignId('order_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('treasury_account_id');
            $table->dropConstrainedForeignId('order_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['amount_collected', 'amount_paid', 'finance_posted_at']);
        });
    }
};
