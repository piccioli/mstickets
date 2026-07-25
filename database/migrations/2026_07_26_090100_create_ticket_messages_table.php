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
        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('author_email')->nullable();
            $table->string('channel', 10);
            $table->string('visibility', 10)->default('public');
            $table->text('body_html')->nullable();
            $table->text('body_text')->nullable();
            // email_messages non esiste ancora (arriva in US-016, §5.2 Email): colonna senza
            // vincolo FK per ora, da completare come FK quando quella story introduce la tabella.
            $table->unsignedBigInteger('email_message_id')->nullable();
            $table->boolean('is_legacy_import')->default(false);
            $table->timestamp('posted_at');
            $table->timestamps();

            $table->index(['ticket_id', 'posted_at']);
            $table->index('author_id');
            $table->index('email_message_id');
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
