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
        Schema::create('schedule_blocks', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('doctor_id')->constrained('users')->cascadeOnDelete();
            
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');
            
            $table->string('reason')->nullable(); // 'Vacation', 'Surgery', 'Conference'
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_blocks');
    }
};
