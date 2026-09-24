<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cai_sections', function (Blueprint $table) {
            // Un booleano non basta più: la verifica di presenza RUNTS ha ora 3 esiti espliciti
            // (registered/not_registered/timeout, {@see App\Domain\CaiDirectory\Enums\CaiRuntsPresenceStatus})
            // oltre a "mai verificata" (null) — un timeout della verifica non è più indistinguibile
            // da "mai verificata".
            $table->string('runts_presence_status')->nullable()->after('runts_registered');
        });

        DB::table('cai_sections')->where('runts_registered', true)->update(['runts_presence_status' => 'registered']);
        DB::table('cai_sections')->where('runts_registered', false)->update(['runts_presence_status' => 'not_registered']);

        // Azzera anche i timestamp: la sostituzione di schema è anche un reset volontario dei dati
        // già scritti dalle verifiche precedenti, per ripartire da zero con la nuova modalità.
        DB::table('cai_sections')->update(['runts_presence_checked_at' => null]);

        Schema::table('cai_sections', function (Blueprint $table) {
            $table->dropColumn('runts_registered');
        });
    }

    public function down(): void
    {
        Schema::table('cai_sections', function (Blueprint $table) {
            $table->boolean('runts_registered')->nullable()->after('runts_presence_status');
        });

        DB::table('cai_sections')->where('runts_presence_status', 'registered')->update(['runts_registered' => true]);
        DB::table('cai_sections')->where('runts_presence_status', 'not_registered')->update(['runts_registered' => false]);

        Schema::table('cai_sections', function (Blueprint $table) {
            $table->dropColumn('runts_presence_status');
        });
    }
};
