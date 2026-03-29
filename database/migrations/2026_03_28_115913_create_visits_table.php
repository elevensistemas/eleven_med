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
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('users')->cascadeOnDelete(); // Quién lo atendió
            
            // Datos Centrales
            $table->longText('motivo_consulta')->nullable();
            $table->longText('diagnostico')->nullable();
            
            // Historia
            $table->longText('antecedentes_oftalmologicos')->nullable();
            $table->longText('tratamiento_oftalmologico')->nullable();
            $table->longText('antecedentes_generales')->nullable();
            $table->longText('tratamientos_generales')->nullable();
            
            // Examen
            $table->string('pio')->nullable();
            $table->string('bmc')->nullable();
            $table->string('obi')->nullable();
            $table->string('otros_examen')->nullable();
            
            // Refracción Lejos (OD = Derecho, OI = Izquierdo)
            $table->string('av_od_lejos')->nullable();
            $table->string('av_oi_lejos')->nullable();
            
            // Refracción Cerca
            $table->string('av_od_cerca')->nullable();
            $table->string('av_oi_cerca')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
