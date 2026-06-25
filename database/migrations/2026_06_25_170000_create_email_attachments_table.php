<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_attachments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('email_id')->constrained()->cascadeOnDelete();
            $table->string('part_key')->comment('IMAP part identifier for deduplication');
            $table->string('filename');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('storage_path');
            $table->timestamps();

            $table->unique(['email_id', 'part_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_attachments');
    }
};
