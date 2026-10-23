<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Territori dei sentieri osm2cai importati via datapack (tabelle `snap_osm2cai_{regions,provinces,sectors,territory_km,
 * trail_territory}`, US-1062). Chiavi `id` = id osm2cai. Solo le 20 regioni italiane (`region_key` = enum `Region`);
 * statistiche per territorio calcolate una volta dallo script (`bin/update-osm2cai-datapack`), mai nell'applicazione.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cai_trail_regions', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name');
            $table->string('region_key')->unique();
        });

        Schema::create('cai_trail_provinces', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('region_id')->index();
            $table->string('name');
            $table->string('code')->nullable();
            $table->foreign('region_id')->references('id')->on('cai_trail_regions')->cascadeOnDelete();
        });

        Schema::create('cai_trail_sectors', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('province_id')->index();
            $table->string('code')->nullable();
            $table->string('full_code')->nullable();
            $table->string('name')->nullable();
            $table->json('geometry')->nullable();
            $table->foreign('province_id')->references('id')->on('cai_trail_provinces')->cascadeOnDelete();
        });

        // `territory_id` punta a regione, provincia o settore secondo `territory_type`: nessuna FK.
        Schema::create('cai_trail_territory_km', function (Blueprint $table): void {
            $table->string('territory_type', 16);
            $table->unsignedBigInteger('territory_id');
            $table->unsignedTinyInteger('status');
            $table->decimal('km', 12, 3);
            $table->unsignedInteger('present_routes');
            $table->primary(['territory_type', 'territory_id', 'status']);
        });

        // Livello del legame = id più profondo valorizzato: regione (provincia e settore null), provincia, settore.
        Schema::create('cai_trail_territory', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('cai_trail_id')->index();
            $table->unsignedBigInteger('region_id')->index();
            $table->unsignedBigInteger('province_id')->nullable()->index();
            $table->unsignedBigInteger('sector_id')->nullable()->index();
            $table->decimal('percentage', 5, 2);
            $table->foreign('cai_trail_id')->references('id')->on('cai_trails')->cascadeOnDelete();
            $table->foreign('region_id')->references('id')->on('cai_trail_regions')->cascadeOnDelete();
            $table->foreign('province_id')->references('id')->on('cai_trail_provinces')->cascadeOnDelete();
            $table->foreign('sector_id')->references('id')->on('cai_trail_sectors')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cai_trail_territory');
        Schema::dropIfExists('cai_trail_territory_km');
        Schema::dropIfExists('cai_trail_sectors');
        Schema::dropIfExists('cai_trail_provinces');
        Schema::dropIfExists('cai_trail_regions');
    }
};
