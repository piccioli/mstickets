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
     * Indice full-text su title e body (US-405, §6.4.2): stessa sintassi
     * Postgres-only (to_tsvector) e stessa guardia sul driver già usate per
     * `tickets.title` — non riproducibile su sqlite (usato dai test, vedi
     * phpunit.xml).
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement(
                "create index documentation_pages_title_fulltext_index on documentation_pages using gin (to_tsvector('italian', title))"
            );
            DB::statement(
                "create index documentation_pages_body_fulltext_index on documentation_pages using gin (to_tsvector('italian', body))"
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('drop index if exists documentation_pages_title_fulltext_index');
            DB::statement('drop index if exists documentation_pages_body_fulltext_index');
        }
    }
};
