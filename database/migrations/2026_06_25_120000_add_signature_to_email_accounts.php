<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_accounts', function (Blueprint $table) {
            $table->longText('signature_html')->nullable()->after('credentials');
            $table->boolean('signature_auto')->default(true)->after('signature_html');
        });
    }

    public function down(): void
    {
        Schema::table('email_accounts', function (Blueprint $table) {
            $table->dropColumn(['signature_html', 'signature_auto']);
        });
    }
};
