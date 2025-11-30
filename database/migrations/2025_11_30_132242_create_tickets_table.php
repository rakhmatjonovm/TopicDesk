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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_user_id')
              ->constrained('telegram_users')
              ->cascadeOnDelete();
            $table->bigInteger('topic_id')->nullable()->index();
            $table->string('status')->default('open')->index(); // open, closed
            $table->string('subject')->nullable(); // Тема обращения
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
