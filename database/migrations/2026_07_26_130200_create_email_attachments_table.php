<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_message_id')->constrained('email_messages')->cascadeOnDelete();
            $table->string('filename');
            $table->string('mime_type');
            $table->unsignedBigInteger('size_bytes');
            $table->string('disk');
            $table->string('path');
            $table->unsignedBigInteger('media_id')->nullable();
            $table->string('status', 20);
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index('email_message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_attachments');
    }
};
