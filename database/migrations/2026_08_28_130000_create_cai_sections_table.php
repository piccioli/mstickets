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
        Schema::create('cai_sections', function (Blueprint $table) {
            // Chiave naturale del datapack RUNTS-CAI (`sezioni_cai.codice_cai`), non un id auto-incrementale.
            $table->string('codice_cai')->primary();
            $table->string('name');
            $table->string('tax_code')->nullable();
            $table->string('vat_number')->nullable();
            $table->string('email')->nullable();
            $table->string('pec')->nullable();
            $table->string('phone_office')->nullable();
            $table->string('phone')->nullable();
            $table->string('fax')->nullable();
            $table->string('address')->nullable();
            $table->string('postal_address')->nullable();
            $table->string('website')->nullable();
            $table->text('office_hours')->nullable();
            $table->text('notices')->nullable();
            $table->smallInteger('founded_year')->nullable();
            $table->integer('members_count')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('region');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('region');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cai_sections');
    }
};
