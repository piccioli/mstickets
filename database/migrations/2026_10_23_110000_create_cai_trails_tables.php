<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sentieri del catasto osm2cai importati via datapack (`snap_osm2cai_trails` / `snap_osm2cai_trail_sections`, US-1052).
 * Chiave `id` = id osm2cai. `practicability` è ricavata da `issues_status` all'import (`CaiTrailPracticability`).
 * `cai_trail_section.section_code` senza FK: osm2cai ha codici di Sezione assenti da `cai_sections`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cai_trails', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedBigInteger('osmid')->nullable();
            $table->string('ref')->nullable()->index();
            $table->text('name')->nullable();
            $table->text('from_place')->nullable();
            $table->text('to_place')->nullable();
            $table->string('cai_scale')->nullable();
            $table->decimal('length_km', 10, 3)->nullable();
            $table->integer('ascent')->nullable();
            $table->integer('descent')->nullable();
            $table->unsignedTinyInteger('status')->index();
            $table->string('issues_status')->nullable();
            $table->string('practicability');
            $table->date('issues_last_update')->nullable();
            $table->date('validation_date')->nullable();
            $table->json('geometry')->nullable();
            $table->timestamps();
        });

        Schema::create('cai_trail_section', function (Blueprint $table): void {
            $table->unsignedBigInteger('cai_trail_id');
            $table->string('section_code', 7)->index();
            $table->primary(['cai_trail_id', 'section_code']);
            $table->foreign('cai_trail_id')->references('id')->on('cai_trails')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cai_trail_section');
        Schema::dropIfExists('cai_trails');
    }
};
