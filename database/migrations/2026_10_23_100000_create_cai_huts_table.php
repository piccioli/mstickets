<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rifugi CAI importati da rifugi.cai.it via datapack (`snap_cai_huts`, US-1031). Chiave naturale `id_cai` della
 * fonte. `branch_code` senza FK: può puntare a codici che non sono Sezioni (Sede Centrale, GR).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cai_huts', function (Blueprint $table): void {
            $table->unsignedBigInteger('id_cai')->primary();
            $table->string('branch_code', 7)->nullable()->index();
            $table->string('branch_name')->nullable();
            $table->string('name');
            $table->string('alias')->nullable();
            $table->string('slug')->nullable();
            $table->string('type')->nullable();
            $table->string('status')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->integer('altitude')->nullable();
            $table->string('locality')->nullable();
            $table->string('municipality')->nullable();
            $table->string('province')->nullable();
            $table->string('region')->nullable();
            $table->string('valley')->nullable();
            $table->string('massif')->nullable();
            $table->string('protected_area')->nullable();
            $table->integer('beds')->nullable();
            $table->integer('winter_beds')->nullable();
            $table->json('opening_times')->nullable();
            $table->json('photos')->nullable();
            $table->json('services')->nullable();
            $table->json('contacts')->nullable();
            $table->json('management')->nullable();
            $table->json('fields')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cai_huts');
    }
};
