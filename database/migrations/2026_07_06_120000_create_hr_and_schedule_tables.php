<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_hr_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('birth_date')->nullable();
            $table->string('national_id', 20)->nullable();
            $table->string('birth_place')->nullable();
            $table->enum('marital_status', ['single', 'married', 'other'])->nullable();
            $table->text('address')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->string('emergency_phone', 30)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('iban', 34)->nullable();
            $table->decimal('base_salary', 14, 2)->nullable();
            $table->string('salary_currency', 3)->default('TRY');
            $table->json('cv_data')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_hr_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('category', 40)->default('other');
            $table->string('title');
            $table->string('path');
            $table->string('disk')->default('local');
            $table->unsignedBigInteger('size')->default(0);
            $table->date('document_date')->nullable();
            $table->date('expires_at')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('employee_compensations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['salary', 'bonus', 'advance', 'deduction', 'other'])->default('salary');
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3)->default('TRY');
            $table->date('payment_date');
            $table->string('period', 20)->nullable();
            $table->string('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('schedule_programs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedTinyInteger('week_number');
            $table->date('week_start');
            $table->date('week_end');
            $table->string('title')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['year', 'month', 'week_number']);
        });

        Schema::create('schedule_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_program_id')->constrained()->cascadeOnDelete();
            $table->date('entry_date')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('data');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_entries');
        Schema::dropIfExists('schedule_programs');
        Schema::dropIfExists('employee_compensations');
        Schema::dropIfExists('employee_hr_documents');
        Schema::dropIfExists('employee_hr_details');
    }
};
