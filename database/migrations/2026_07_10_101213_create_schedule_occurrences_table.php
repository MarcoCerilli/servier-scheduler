<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_occurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('schedule_id')
                  ->constrained('schedules')
                  ->cascadeOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->index(['schedule_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_occurrences');
    }
};
