<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Indice full-text su body_text (US-603, §8.7 — ricerca globale sul corpo dei
     * messaggi): stessa sintassi Postgres-only (to_tsvector) e stessa guardia sul
     * driver già usate per `tickets.title`/`documentation_pages.title|body` — non
     * riproducibile su sqlite (usato dai test, vedi phpunit.xml).
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement(
                "create index ticket_messages_body_text_fulltext_index on ticket_messages using gin (to_tsvector('italian', body_text))"
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('drop index if exists ticket_messages_body_text_fulltext_index');
        }
    }
};
