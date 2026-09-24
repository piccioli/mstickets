<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cai_sections', function (Blueprint $table) {
            // Fase 9: valorizzata solo dal comando cai:check-runts-presence (verifica leggera
            // via ricerca RUNTS, mai lo scrape completo). `null` finché la sezione non è mai
            // stata verificata; `true`/`false` è l'esito dell'ultima verifica.
            $table->boolean('runts_registered')->nullable();
            $table->timestamp('runts_presence_checked_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('cai_sections', function (Blueprint $table) {
            $table->dropColumn(['runts_registered', 'runts_presence_checked_at']);
        });
    }
};
