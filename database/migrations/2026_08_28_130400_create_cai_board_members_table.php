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
        // Tabella vuota all'origine (`cariche_sociali` non ha ancora dati nel datapack): struttura
        // pronta per un futuro arricchimento della fonte, non un dato fittizio.
        Schema::create('cai_board_members', function (Blueprint $table) {
            $table->id();
            $table->string('cai_runts_registration_id');
            $table->string('role');
            $table->string('full_name')->nullable();
            $table->string('tax_code')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();

            $table->foreign('cai_runts_registration_id', 'cai_board_members_registration_fk')
                ->references('id_runts')->on('cai_runts_registrations')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cai_board_members');
    }
};
