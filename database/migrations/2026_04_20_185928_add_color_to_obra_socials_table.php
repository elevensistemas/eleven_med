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
        Schema::table('obra_socials', function (Blueprint $table) {
            $table->string('color')->default('#5e6ad2')->after('name');
        });
        
        // Asignar colores por defecto conocidos si existen
        DB::table('obra_socials')->where('name', 'like', '%osde%')->update(['color' => '#0d6efd']);
        DB::table('obra_socials')->where('name', 'like', '%galeno%')->update(['color' => '#0dcaf0']);
        DB::table('obra_socials')->where('name', 'like', '%italiano%')->update(['color' => '#198754']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('obra_socials', function (Blueprint $table) {
            //
        });
    }
};
