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
        Schema::create('cai_runts_registrations', function (Blueprint $table) {
            // Chiave naturale del datapack RUNTS-CAI (`enti.id_runts`).
            $table->string('id_runts')->primary();
            $table->string('cai_section_id')->nullable();
            $table->string('tax_code')->nullable();
            $table->string('name')->nullable();
            $table->string('legal_form')->nullable();
            $table->string('legal_nature')->nullable();
            $table->string('address')->nullable();
            $table->string('street_number')->nullable();
            $table->string('municipality')->nullable();
            $table->string('province')->nullable();
            $table->string('region')->nullable();
            $table->string('postal_code')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->date('registration_date')->nullable();
            $table->string('register_section')->nullable();
            $table->text('activity_sectors')->nullable();
            $table->string('legal_representative')->nullable();
            $table->string('website')->nullable();
            $table->string('pec')->nullable();
            $table->string('official_page_url')->nullable();
            $table->timestamps();

            $table->foreign('cai_section_id')->references('codice_cai')->on('cai_sections')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cai_runts_registrations');
    }
};
