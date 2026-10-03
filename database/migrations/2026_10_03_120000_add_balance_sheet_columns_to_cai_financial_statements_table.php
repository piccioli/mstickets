<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Totali dello stato patrimoniale estratti dai PDF Mod A (US-942/US-943): affiancano le cifre del conto
 * economico già presenti sulla stessa riga (registrazione/sezione, anno).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cai_financial_statements', function (Blueprint $table) {
            $table->decimal('total_assets', 15, 2)->nullable();
            $table->decimal('total_liabilities', 15, 2)->nullable();
            $table->decimal('net_equity', 15, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('cai_financial_statements', function (Blueprint $table) {
            $table->dropColumn(['total_assets', 'total_liabilities', 'net_equity']);
        });
    }
};
