<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Traccia l'esito dell'estrazione finanziaria per SINGOLO documento (Fase 9, seguito alla story
 * "review parser bilanci"): prima di questa migrazione l'unico esito osservabile era il record
 * `cai_financial_statements`, condiviso (merge campo-per-campo) fra tutti i documenti di uno stesso
 * (registrazione, anno) — un documento il cui parsing fallisce del tutto può restare "invisibile" se un
 * altro documento per lo stesso anno ha invece prodotto dati buoni. Queste colonne vivono sul documento
 * stesso, non sul risultato aggregato.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cai_documents', function (Blueprint $table) {
            $table->string('financial_analysis_status')->nullable()->after('hash');
            $table->text('raw_text_excerpt')->nullable()->after('financial_analysis_status');
            $table->boolean('extracted_via_ocr')->nullable()->after('raw_text_excerpt');
        });
    }

    public function down(): void
    {
        Schema::table('cai_documents', function (Blueprint $table) {
            $table->dropColumn(['financial_analysis_status', 'raw_text_excerpt', 'extracted_via_ocr']);
        });
    }
};
