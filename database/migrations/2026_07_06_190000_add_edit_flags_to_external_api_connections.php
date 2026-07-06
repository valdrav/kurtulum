<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_api_connections', function (Blueprint $table) {
            $table->boolean('edit_customer')->default(false)->after('view_customer');
            $table->boolean('edit_directory')->default(false)->after('view_directory');
        });
    }

    public function down(): void
    {
        Schema::table('external_api_connections', function (Blueprint $table) {
            $table->dropColumn(['edit_customer', 'edit_directory']);
        });
    }
};
