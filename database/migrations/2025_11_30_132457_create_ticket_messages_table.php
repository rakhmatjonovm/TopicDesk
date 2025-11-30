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
        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('ticket_id')
              ->constrained('tickets')
              ->cascadeOnDelete();
            $table->enum('direction', ['incoming', 'outgoing'])->index();
            $table->text('content')->nullable();
            $table->bigInteger('user_message_id')->nullable(); // В личке с ботом
            $table->bigInteger('support_message_id')->nullable(); // В топике группы
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_messages');
    }
};
