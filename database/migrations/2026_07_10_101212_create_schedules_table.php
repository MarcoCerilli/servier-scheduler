<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Tokens
            $table->string('public_token', 64)->unique();
            $table->string('management_token', 64)->unique();

            // Metadati
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('timezone', 64)->default('Europe/Rome');

            // Periodo
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->unsignedSmallInteger('duration_days')->nullable();

            // Frequenza
            $table->enum('frequency', ['once', 'daily', 'weekly', 'monthly']);
            $table->json('days_of_week')->nullable();     // [1,2,3] Mon=1 Sun=7
            $table->json('times_of_day');                  // ["09:00","14:00"]

            // Evento
            $table->unsignedSmallInteger('event_duration_minutes')->default(60);

            // Eccezioni e promemoria
            $table->json('excluded_dates')->nullable();   // ["2024-12-25"]
            $table->unsignedInteger('reminder_minutes')->nullable(); // 0 = no reminder

            // QR Code
            $table->string('qr_code_path')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
