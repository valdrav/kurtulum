<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_translations', function (Blueprint $table) {
            $table->id();
            $table->char('text_hash', 64);
            $table->string('source_locale', 8);
            $table->string('target_locale', 8);
            $table->text('source_text');
            $table->text('translated_text');
            $table->timestamps();

            $table->unique(['text_hash', 'target_locale']);
            $table->index(['target_locale', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_translations');
    }
};
