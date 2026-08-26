<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fix del bug noto US-321/US-326: il badge di notifiche di Filament interroga
 * `data->>'format'` (operatore JSON), ma `notifications.data` era `text` —
 * su PostgreSQL questo produce SQLSTATE[42883] "operator does not exist: text ->>
 * unknown" su OGNI pagina autenticata del pannello. SQLite non applica tipizzazione
 * alle colonne quindi non manifesta il problema, da cui la guardia pgsql-only (stesso
 * pattern già in uso altrove nel repo per sintassi Postgres non portabile). Migrazione
 * dedicata separata: la tabella originale (US-312) non va modificata.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('alter table notifications alter column data type json using data::json');
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('alter table notifications alter column data type text');
        }
    }
};
