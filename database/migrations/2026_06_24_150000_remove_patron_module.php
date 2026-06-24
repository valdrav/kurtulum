<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('patron_reviews');

        if (Schema::hasColumn('tasks', 'patron_assigned')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropColumn('patron_assigned');
            });
        }

        Permission::where('name', 'like', 'patron.%')->delete();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        //
    }
};
