<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // Fase 7 (US-701): tipo cliente CAI (Sezione/GruppoRegionale/OTCO-SO/Generico) e
            // regione di appartenenza. Nullable e senza vincolo DB: pertinenti solo per gli
            // utenti con ruolo `customer`, dedotti dall'ETL (US-702) o assegnati da un admin
            // (US-703). `region` è concettualmente valorizzata solo per Sezione/GruppoRegionale,
            // ma questa è una convenzione applicativa, non un vincolo di schema.
            $table->string('customer_type')->nullable()->after('deactivated_at');
            $table->string('region')->nullable()->after('customer_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['customer_type', 'region']);
        });
    }
};
