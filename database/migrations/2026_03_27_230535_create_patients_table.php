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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            
            // Core Identity
            $table->string('last_name');
            $table->string('first_name');
            $table->string('dni')->unique()->index();
            $table->date('date_of_birth');
            
            // Financial & Affiliation
            $table->string('obra_social')->nullable();
            $table->string('plan')->nullable();
            $table->string('affiliate_number')->nullable();
            $table->string('iva_condition')->nullable();
            $table->string('prepaga')->nullable();
            
            // Contact & Meta
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            
            // Clinical & Office Metadata
            $table->string('code')->nullable(); // Unique code
            $table->string('profession')->nullable();
            $table->string('recommendation')->nullable();
            $table->string('nro_siniestro')->nullable(); // Claim number
            
            // Linkages
            $table->foreignId('director_id')->nullable()->constrained('users')->nullOnDelete();
            
            // File Attachment legacy/external
            $table->longText('external_file_path')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
