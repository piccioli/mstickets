<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->nullable()->constrained('tickets')->cascadeOnDelete();
            $table->string('subject_normalized');
            $table->jsonb('participants')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index('ticket_id');
            $table->index('subject_normalized');
            $table->index('last_message_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_threads');
    }
};
