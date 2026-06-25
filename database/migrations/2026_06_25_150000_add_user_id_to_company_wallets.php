<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_wallets', function (Blueprint $table) {
            if (! Schema::hasColumn('company_wallets', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('uuid')->constrained()->cascadeOnDelete();
                $table->index(['user_id', 'is_active']);
            }
        });

        $fallbackUserId = DB::table('users')->where('is_active', true)->orderBy('id')->value('id');

        if ($fallbackUserId) {
            DB::table('company_wallets')->whereNull('user_id')->update(['user_id' => $fallbackUserId]);
        }
    }

    public function down(): void
    {
        Schema::table('company_wallets', function (Blueprint $table) {
            if (Schema::hasColumn('company_wallets', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
        });
    }
};
