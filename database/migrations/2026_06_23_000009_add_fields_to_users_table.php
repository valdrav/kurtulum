<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
            $table->string('locale', 5)->default('tr')->after('password');
            $table->string('theme')->default('light')->after('locale');
            $table->string('phone')->nullable()->after('theme');
            $table->string('avatar')->nullable()->after('phone');
            $table->boolean('is_active')->default(true)->after('avatar');
            $table->foreignId('department_id')->nullable()->after('is_active')->constrained()->nullOnDelete();
            $table->softDeletes();
        });

        foreach (DB::table('users')->whereNull('uuid')->pluck('id') as $id) {
            DB::table('users')->where('id', $id)->update([
                'uuid' => (string) Str::uuid(),
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('uuid');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropConstrainedForeignId('department_id');
            $table->dropColumn(['uuid', 'locale', 'theme', 'phone', 'avatar', 'is_active']);
        });
    }
};
