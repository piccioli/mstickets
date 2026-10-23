<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tappe del Sentiero Italia CAI (US-1091): una riga per sentiero osm2cai di un itinerario "Sentiero Italia …", importata
 * da `snap_osm2cai_sicai_stages` del datapack. Codice, lettera, numero e variante ricavati dal nome del sentiero o
 * dell'itinerario (`CaiSicaiStageCode`); `sort_key` ordina per lettera, numero, variante.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cai_sicai_stages', function (Blueprint $table): void {
            $table->unsignedBigInteger('cai_trail_id')->primary();
            $table->string('itinerary_name');
            $table->string('letter', 1)->nullable()->index();
            $table->unsignedSmallInteger('number')->nullable();
            $table->string('variant', 16)->nullable();
            $table->string('code', 32);
            $table->string('sort_key', 64)->index();
            $table->timestamps();
            $table->foreign('cai_trail_id')->references('id')->on('cai_trails')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cai_sicai_stages');
    }
};
