<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_messages', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->string('direction', 10);
            $table->string('message_id', 998)->nullable();
            $table->string('in_reply_to', 998)->nullable();
            $table->text('references')->nullable();
            $table->foreignId('thread_id')->nullable()->constrained('email_threads')->nullOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_email');
            $table->string('from_name')->nullable();
            $table->jsonb('to')->nullable();
            $table->jsonb('cc')->nullable();
            $table->jsonb('bcc')->nullable();
            $table->string('reply_to')->nullable();
            $table->text('subject')->nullable();
            $table->text('body_text')->nullable();
            $table->text('body_html')->nullable();
            $table->string('raw_path')->nullable();
            $table->string('status', 15);
            $table->text('failure_reason')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->string('mailable_class')->nullable();
            $table->string('provider_message_id')->nullable();
            $table->unsignedInteger('imap_uid')->nullable();
            $table->string('imap_folder')->nullable();
            $table->char('content_hash', 64)->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('ticket_id');
            $table->index('content_hash');
            $table->index('thread_id');

            // Vincoli unique() standard: NULL è distinto da se stesso in un vincolo UNIQUE
            // (a differenza del caso coalesce di activity_reports), quindi più righe con
            // message_id/imap_uid NULL non violano il vincolo — esattamente il comportamento
            // richiesto da §5.2 ("dove message_id non null"/"per l'inbound": per le righe
            // outbound imap_folder/imap_uid restano NULL e non partecipano al controllo).
            $table->unique(['direction', 'message_id']);
            $table->unique(['imap_folder', 'imap_uid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_messages');
    }
};
