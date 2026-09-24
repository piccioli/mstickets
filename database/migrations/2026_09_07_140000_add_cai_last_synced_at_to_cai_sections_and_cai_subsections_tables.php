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
        Schema::table('cai_sections', function (Blueprint $table) {
            // Fase 9, storia 1: valorizzata solo da un refresh live (bottone dashboard o
            // `cai:sync-national`), resta `null` per una riga proveniente solo dal vecchio
            // import da datapack statico (`cai:import-datapack`, mai sincronizzata dal vivo).
            $table->timestamp('cai_last_synced_at')->nullable();
        });

        Schema::table('cai_subsections', function (Blueprint $table) {
            $table->timestamp('cai_last_synced_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cai_sections', function (Blueprint $table) {
            $table->dropColumn('cai_last_synced_at');
        });

        Schema::table('cai_subsections', function (Blueprint $table) {
            $table->dropColumn('cai_last_synced_at');
        });
    }
};
