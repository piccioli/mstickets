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
        Schema::create('cai_subsections', function (Blueprint $table) {
            // Chiave naturale del datapack RUNTS-CAI (`sottosezioni_cai.cai_codice`).
            $table->string('cai_codice')->primary();
            $table->string('cai_section_id');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone_office')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('website')->nullable();
            $table->text('office_hours')->nullable();
            $table->text('notices')->nullable();
            $table->smallInteger('founded_year')->nullable();
            $table->integer('members_count')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('cai_section_id')->references('codice_cai')->on('cai_sections')->cascadeOnDelete();
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cai_subsections');
    }
};
