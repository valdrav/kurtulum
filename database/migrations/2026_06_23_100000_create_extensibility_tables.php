<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->string('native_name')->nullable();
            $table->enum('direction', ['ltr', 'rtl'])->default('ltr');
            $table->string('flag', 10)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('system_currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->string('symbol', 10)->default('');
            $table->unsignedTinyInteger('decimal_places')->default(2);
            $table->decimal('exchange_rate', 18, 8)->default(1);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['payment', 'collection', 'both'])->default('both');
            $table->string('icon', 50)->default('ti-cash');
            $table->json('config_schema')->nullable()->comment('Dynamic form fields definition');
            $table->json('settings')->nullable()->comment('Configured values (API keys, account numbers, etc.)');
            $table->json('features')->nullable()->comment('Enabled capabilities flags');
            $table->json('supported_currencies')->nullable()->comment('null = all active currencies');
            $table->enum('fee_type', ['none', 'fixed', 'percent'])->default('none');
            $table->decimal('fee_amount', 15, 4)->default(0);
            $table->boolean('requires_reference')->default(false);
            $table->boolean('requires_bank_account')->default(false);
            $table->boolean('is_online')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('system_modules', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('name');
            $table->string('version', 20)->default('1.0.0');
            $table->text('description')->nullable();
            $table->string('provider_class')->nullable();
            $table->string('path')->nullable();
            $table->json('manifest')->nullable();
            $table->json('config')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->boolean('is_core')->default(false);
            $table->timestamp('installed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('lookup_types', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('lookup_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lookup_type_id')->constrained()->cascadeOnDelete();
            $table->string('code', 100);
            $table->string('label');
            $table->json('meta')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['lookup_type_id', 'code']);
        });

        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'payment_method_id')) {
                $table->foreignId('payment_method_id')->nullable()->after('currency')->constrained('payment_methods')->nullOnDelete();
            }
            if (!Schema::hasColumn('payments', 'method_data')) {
                $table->json('method_data')->nullable()->after('payment_method_id');
            }
            if (!Schema::hasColumn('payments', 'exchange_rate')) {
                $table->decimal('exchange_rate', 12, 6)->default(1)->after('currency');
            }
            if (!Schema::hasColumn('payments', 'fee_amount')) {
                $table->decimal('fee_amount', 15, 2)->default(0)->after('amount');
            }
        });

        Schema::table('collections', function (Blueprint $table) {
            if (!Schema::hasColumn('collections', 'payment_method_id')) {
                $table->foreignId('payment_method_id')->nullable()->after('currency')->constrained('payment_methods')->nullOnDelete();
            }
            if (!Schema::hasColumn('collections', 'method_data')) {
                $table->json('method_data')->nullable()->after('payment_method_id');
            }
            if (!Schema::hasColumn('collections', 'exchange_rate')) {
                $table->decimal('exchange_rate', 12, 6)->default(1)->after('currency');
            }
            if (!Schema::hasColumn('collections', 'fee_amount')) {
                $table->decimal('fee_amount', 15, 2)->default(0)->after('amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropForeign(['payment_method_id']);
            $table->dropColumn(['payment_method_id', 'method_data', 'exchange_rate', 'fee_amount']);
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['payment_method_id']);
            $table->dropColumn(['payment_method_id', 'method_data', 'exchange_rate', 'fee_amount']);
        });
        Schema::dropIfExists('lookup_values');
        Schema::dropIfExists('lookup_types');
        Schema::dropIfExists('system_modules');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('system_currencies');
        Schema::dropIfExists('system_languages');
    }
};
