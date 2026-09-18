<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Un `CaiDocument`/`CaiFinancialStatement` con `source` `manual`/`veryfico` (vedi migrazione precedente)
 * può non avere ALCUNA `CaiRuntsRegistration` a cui appoggiarsi — a differenza di tutto ciò che è stato
 * sincronizzato dal vivo (`source: runts`), che ne ha sempre una. `cai_runts_registration_id` diventa
 * quindi nullable su entrambe le tabelle, e viene aggiunta una `cai_section_id` alternativa (nullable,
 * FK verso `cai_sections`). Invariante "esattamente uno dei due genitori" imposta SOLO a livello di
 * Action (`UploadCaiDocumentManually`, `AnalyzeCaiFinancialStatementDocument`), mai con un CHECK a
 * livello DB: SQLite (i test) non supporta l'aggiunta di un CHECK via ALTER TABLE in modo portabile,
 * mentre ogni riga di queste due tabelle è comunque scritta solo da un'Action esplicita (mai una via
 * di scrittura alternativa) — vedi `app/Domain/CaiDirectory/CLAUDE.md`.
 *
 * **Non serve droppare/ricreare la foreign key esistente su `cai_runts_registration_id`** per renderla
 * nullable: un vincolo FK su una colonna nullable è normale SQL (NULL non viene mai verificato contro
 * la FK) — `->change()` da solo basta. Evitarlo è anche l'unico modo per restare portabile: SQLite (i
 * test) non supporta `dropForeign()` per nome vincolo, solo per colonna, e quel path avrebbe comunque
 * richiesto una guardia per-driver diversa da Postgres (dove il nome esplicito resta necessario).
 *
 * Richiede `doctrine/dbal` (introdotto da questa story): primo caso nel repo in cui una colonna già
 * NOT NULL deve diventare nullable *realmente su entrambi i driver* (non solo lato Postgres come nella
 * migrazione `cai_last_synced_at`/notifications, dove SQLite non aveva bisogno del cambio per
 * funzionare — qui invece serve scrivere righe con quel campo null anche nei test su SQLite).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cai_documents', function (Blueprint $table) {
            $table->string('cai_runts_registration_id')->nullable()->change();
        });

        Schema::table('cai_documents', function (Blueprint $table) {
            $table->string('cai_section_id')->nullable()->after('cai_runts_registration_id');
        });

        Schema::table('cai_documents', function (Blueprint $table) {
            $table->foreign('cai_section_id')
                ->references('codice_cai')->on('cai_sections')->cascadeOnDelete();
        });

        Schema::table('cai_financial_statements', function (Blueprint $table) {
            $table->string('cai_runts_registration_id')->nullable()->change();
        });

        Schema::table('cai_financial_statements', function (Blueprint $table) {
            $table->string('cai_section_id')->nullable()->after('cai_runts_registration_id');
        });

        Schema::table('cai_financial_statements', function (Blueprint $table) {
            $table->foreign('cai_section_id')
                ->references('codice_cai')->on('cai_sections')->cascadeOnDelete();
            $table->unique(['cai_section_id', 'year'], 'cai_financial_statements_section_year_unique');
        });
    }

    public function down(): void
    {
        Schema::table('cai_financial_statements', function (Blueprint $table) {
            $table->dropUnique('cai_financial_statements_section_year_unique');
            $table->dropForeign(['cai_section_id']);
            $table->dropColumn('cai_section_id');
        });

        Schema::table('cai_financial_statements', function (Blueprint $table) {
            $table->string('cai_runts_registration_id')->nullable(false)->change();
        });

        Schema::table('cai_documents', function (Blueprint $table) {
            $table->dropForeign(['cai_section_id']);
            $table->dropColumn('cai_section_id');
        });

        Schema::table('cai_documents', function (Blueprint $table) {
            $table->string('cai_runts_registration_id')->nullable(false)->change();
        });
    }
};
