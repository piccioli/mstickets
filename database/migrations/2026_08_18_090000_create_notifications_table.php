<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabella standard Laravel per `Illuminate\Notifications\Notifiable` (§7.5.2 del PRD,
 * US-312): richiesta da `Filament\Notifications\Notification::sendToDatabase()`, usata
 * per la prima volta in questa fase per E3/E9 (notifica in-app allo staff). Distinta dal
 * dominio Mail (email_messages/email_threads/...) esistente da Fase 0: questa è
 * infrastruttura di framework, non schema applicativo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
