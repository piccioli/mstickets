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
        Schema::table('tickets', function (Blueprint $table): void {
            // US-611 (§10.2, comando `tickets:archive-scrum`): comportamento v1 non
            // recuperabile con certezza dal dump disponibile (nessun comando/colonna
            // di archiviazione trovato — solo viste Nova "Archived*" in sola lettura
            // filtrate per stato, nessuna mutazione). Lettura conservativa adottata:
            // un flag additivo, mai una cancellazione né un cambio di `status`.
            $table->timestamp('archived_at')->nullable()->after('done_at');
            $table->index('archived_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table): void {
            $table->dropIndex(['archived_at']);
            $table->dropColumn('archived_at');
        });
    }
};
