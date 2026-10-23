<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Punti di appoggio vicini alle tappe SICAI (US-1093): i punti entro 1,5 km dal tracciato della tappa, con la distanza
 * (`distance_m`) e la posizione lungo il tracciato del punto più vicino (`position`, metri dall'inizio, per l'ordine di
 * percorrenza). Calcolata da `cai:import-datapack` dopo tappe e punti, ricalcolata a ogni import.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cai_sicai_stage_rest_point', function (Blueprint $table): void {
            $table->unsignedBigInteger('cai_trail_id');
            $table->unsignedBigInteger('rest_point_id')->index();
            $table->unsignedInteger('distance_m');
            $table->unsignedInteger('position');
            $table->primary(['cai_trail_id', 'rest_point_id']);
            $table->foreign('cai_trail_id')->references('cai_trail_id')->on('cai_sicai_stages')->cascadeOnDelete();
            $table->foreign('rest_point_id')->references('id')->on('cai_sicai_rest_points')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cai_sicai_stage_rest_point');
    }
};
