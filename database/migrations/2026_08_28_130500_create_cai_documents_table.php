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
        Schema::create('cai_documents', function (Blueprint $table) {
            $table->id();
            $table->string('cai_runts_registration_id');
            $table->string('document_type');
            $table->smallInteger('year')->nullable();
            $table->string('title')->nullable();
            // Riferimento al file nello storage privato (stesso disco/pattern degli allegati ticket, Fase 1).
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('hash', 64)->nullable();
            $table->timestamps();

            $table->foreign('cai_runts_registration_id', 'cai_documents_registration_fk')
                ->references('id_runts')->on('cai_runts_registrations')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cai_documents');
    }
};
