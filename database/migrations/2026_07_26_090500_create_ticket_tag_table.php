<?php

declare(strict_types=1);

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
        Schema::create('ticket_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            // tags non esiste ancora (arriva in US-013, §5.2 Tag/commesse): colonna senza
            // vincolo FK per ora, da completare come FK quando quella story introduce la
            // tabella (stesso pattern di tickets.fundraising_project_id/ticket_messages.email_message_id).
            $table->unsignedBigInteger('tag_id');
            $table->timestamps();

            $table->unique(['ticket_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_tag');
    }
};
