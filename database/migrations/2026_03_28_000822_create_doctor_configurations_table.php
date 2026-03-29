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
        Schema::create('doctor_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('users')->cascadeOnDelete();
            
            // Array/JSON of working days (e.g. ["1","2","3","4","5"] where 1 is Monday)
            $table->json('working_days')->nullable();
            
            // Primary Shift
            $table->time('shift_1_start')->nullable();
            $table->time('shift_1_end')->nullable();
            
            // Secondary Split-Shift (optional)
            $table->time('shift_2_start')->nullable();
            $table->time('shift_2_end')->nullable();
            
            // Default slot duration
            $table->integer('appointment_duration')->default(15);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_configurations');
    }
};
