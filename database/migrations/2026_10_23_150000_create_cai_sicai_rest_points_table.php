<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Punti di appoggio del Sentiero Italia CAI importati da rifugi.cai.it via datapack (`snap_cai_sicai_rest_points`,
 * US-1092). Chiave naturale `id` della fonte; `cai_hut_id_cai` valorizzato solo se il `shelter_id` della fonte è un
 * rifugio importato in `cai_huts`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cai_sicai_rest_points', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->string('name');
            $table->string('type')->nullable()->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('region')->nullable()->index();
            $table->string('municipality')->nullable();
            // Testo libero della fonte: `website` arriva anche a ~350 caratteri (link "safelinks" di Outlook).
            $table->text('address')->nullable();
            $table->text('operator')->nullable();
            $table->text('website')->nullable();
            $table->text('email')->nullable();
            $table->text('phone')->nullable();
            $table->unsignedBigInteger('cai_hut_id_cai')->nullable()->index();
            $table->json('fields')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamps();
            $table->foreign('cai_hut_id_cai')->references('id_cai')->on('cai_huts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cai_sicai_rest_points');
    }
};
