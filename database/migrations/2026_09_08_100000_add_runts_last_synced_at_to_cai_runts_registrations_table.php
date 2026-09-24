<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cai_runts_registrations', function (Blueprint $table) {
            // Fase 9, storia 3: valorizzata solo da SyncCaiRuntsRegistration (scrape live),
            // resta null per una registrazione importata solo dal datapack statico.
            $table->timestamp('runts_last_synced_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('cai_runts_registrations', function (Blueprint $table) {
            $table->dropColumn('runts_last_synced_at');
        });
    }
};
