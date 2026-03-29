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
        Schema::create('patient_assignments', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('users')->nullOnDelete();
            
            $table->string('event_type'); // Dilatación, Consulta, Estudio, etc.
            $table->enum('status', ['in_progress', 'completed'])->default('in_progress');
            $table->text('notes')->nullable();
            
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_assignments');
    }
};
