<?php

declare(strict_types=1);

namespace Tests\Feature\Import\Fixtures;

use Illuminate\Support\Facades\DB;

/**
 * Sostituisce la connessione `legacy` (pgsql verso `db_legacy`, irraggiungibile in
 * test/CI, vedi CLAUDE.md sezione ETL) con sqlite in-memory, così che uno stage
 * reale (US-202+) sia testabile end-to-end sulla propria lettura/mapping senza il
 * container `db_legacy`. Il test che usa questo trait crea con
 * `Schema::connection('legacy')` solo le tabelle v1 di cui ha bisogno.
 */
trait InteractsWithLegacyDatabase
{
    protected function useSqliteLegacyConnection(): void
    {
        config()->set('database.connections.legacy', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        DB::purge('legacy');
    }
}
