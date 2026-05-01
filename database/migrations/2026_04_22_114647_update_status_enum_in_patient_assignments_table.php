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
        Schema::table('patient_assignments', function (Blueprint $table) {
            \DB::statement("ALTER TABLE patient_assignments MODIFY status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patient_assignments', function (Blueprint $table) {
            \DB::statement("ALTER TABLE patient_assignments MODIFY status ENUM('in_progress', 'completed') DEFAULT 'in_progress'");
        });
    }
};
