<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riquadro (bbox WGS84) della geometria di ogni sentiero (US-1064), per caricare sulla mappa del GR solo i sentieri
 * dell'area visibile senza decodificare il GeoJSON. Lo valorizza `CaiTrailsImporter` a ogni `cai:import-datapack`
 * (null senza geometria): le righe già importate si completano al primo import successivo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cai_trails', function (Blueprint $table): void {
            $table->decimal('min_lat', 9, 6)->nullable();
            $table->decimal('min_lng', 9, 6)->nullable();
            $table->decimal('max_lat', 9, 6)->nullable();
            $table->decimal('max_lng', 9, 6)->nullable();
            $table->index(['min_lng', 'max_lng']);
        });
    }

    public function down(): void
    {
        Schema::table('cai_trails', function (Blueprint $table): void {
            $table->dropIndex(['min_lng', 'max_lng']);
            $table->dropColumn(['min_lat', 'min_lng', 'max_lat', 'max_lng']);
        });
    }
};
