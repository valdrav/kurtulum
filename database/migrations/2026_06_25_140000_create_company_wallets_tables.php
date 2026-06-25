<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_wallets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('holder_name')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('iban', 34)->nullable();
            $table->string('currency', 3)->default('TRY');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_wallet_id')->constrained()->cascadeOnDelete();
            $table->string('type')->comment('deposit, expense');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('TRY');
            $table->string('description');
            $table->string('counterparty')->nullable()->comment('Gönderen veya harcama yeri');
            $table->string('receipt_no')->nullable();
            $table->text('notes')->nullable();
            $table->date('transaction_date');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_wallet_id', 'transaction_date']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('company_wallets');
    }
};
