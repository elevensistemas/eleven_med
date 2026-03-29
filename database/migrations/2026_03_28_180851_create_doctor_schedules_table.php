<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('doctor_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('users')->onDelete('cascade');
            $table->integer('day_of_week')->comment('0=Sun, 1=Mon, ..., 6=Sat');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('slot_duration_minutes')->default(15);
            $table->timestamps();
            
            // Un doctor no puede tener dos configuraciones para el mismo día
            $table->unique(['doctor_id', 'day_of_week']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_schedules');
    }
};
