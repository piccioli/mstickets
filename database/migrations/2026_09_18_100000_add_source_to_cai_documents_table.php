<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `source` distingue come un `CaiDocument` è arrivato in anagrafica (`CaiDocumentSource`):
 * `runts` (sync live dal portale RUNTS, unico percorso esistito finora), `manual` (upload
 * da un membro dello staff), `veryfico` (integrazione futura, valore riservato, nessuna
 * integrazione reale oggi). `->default('runts')` backfilla automaticamente ogni riga già
 * presente (ogni `CaiDocument` esistente viene davvero da RUNTS, mai da un altro percorso).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cai_documents', function (Blueprint $table) {
            $table->string('source')->default('runts')->after('extracted_via_ocr');
        });
    }

    public function down(): void
    {
        Schema::table('cai_documents', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
