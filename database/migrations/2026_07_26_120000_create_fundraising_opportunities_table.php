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
        Schema::create('fundraising_opportunities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('official_url')->nullable();
            $table->decimal('endowment_fund', 15, 2)->nullable();
            $table->date('deadline');
            $table->string('program_name')->nullable();
            $table->string('sponsor')->nullable();
            $table->decimal('cofinancing_quota', 5, 2)->nullable();
            $table->decimal('max_contribution', 15, 2)->nullable();
            $table->string('territorial_scope', 20)->default('national');
            $table->text('beneficiary_requirements')->nullable();
            $table->text('lead_requirements')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('responsible_user_id')->constrained('users');
            $table->foreignId('evaluated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('evaluated_at')->nullable();
            $table->smallInteger('evaluation_positive_total')->nullable();
            $table->smallInteger('evaluation_negative_total')->nullable();
            $table->smallInteger('evaluation_total')->nullable();
            $table->timestamps();

            $table->index('deadline');
            $table->index('territorial_scope');
            $table->index('created_by');
            $table->index('responsible_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fundraising_opportunities');
    }
};
