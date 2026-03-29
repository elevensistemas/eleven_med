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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            
            // Relational
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('users')->cascadeOnDelete(); // Doctor attending
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); // Who booked it
            
            // Scheduling
            $table->date('date');
            $table->time('time');
            $table->integer('duration_minutes')->default(15);
            $table->boolean('is_overbooked')->default(false); // If true, slot ignores conflict rules
            
            // Metadata
            $table->string('reason')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show'])->default('pending');
            $table->longText('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
