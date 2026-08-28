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
        Schema::create('cai_financial_statements', function (Blueprint $table) {
            $table->id();
            $table->string('cai_runts_registration_id');
            $table->smallInteger('year');
            $table->decimal('general_interest_expenses', 15, 2)->nullable();
            $table->decimal('other_activities_expenses', 15, 2)->nullable();
            $table->decimal('fundraising_expenses', 15, 2)->nullable();
            $table->decimal('financial_expenses', 15, 2)->nullable();
            $table->decimal('overhead_expenses', 15, 2)->nullable();
            $table->decimal('total_expenses', 15, 2)->nullable();
            $table->decimal('general_interest_revenues', 15, 2)->nullable();
            $table->decimal('other_activities_revenues', 15, 2)->nullable();
            $table->decimal('fundraising_revenues', 15, 2)->nullable();
            $table->decimal('financial_revenues', 15, 2)->nullable();
            $table->decimal('overhead_revenues', 15, 2)->nullable();
            $table->decimal('total_revenues', 15, 2)->nullable();
            $table->decimal('pre_tax_result', 15, 2)->nullable();
            $table->decimal('taxes', 15, 2)->nullable();
            $table->decimal('net_result', 15, 2)->nullable();
            $table->timestamps();

            $table->foreign('cai_runts_registration_id', 'cai_financial_statements_registration_fk')
                ->references('id_runts')->on('cai_runts_registrations')->cascadeOnDelete();
            $table->unique(['cai_runts_registration_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cai_financial_statements');
    }
};
