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
        Schema::create('whatsapp_configs', function (Blueprint $table) {
            $table->id();
            $table->string('api_url')->nullable();
            $table->string('instance_id')->nullable();
            $table->string('token')->nullable();
            $table->integer('reminder_days_before')->default(1);
            $table->time('reminder_time')->default('08:00:00');
            $table->text('message_template')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_configs');
    }
};
