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
        Schema::create('fundraising_evaluation_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fundraising_opportunity_id')->constrained('fundraising_opportunities')->cascadeOnDelete();
            $table->string('criterion_key', 40);
            $table->smallInteger('score');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['fundraising_opportunity_id', 'criterion_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fundraising_evaluation_scores');
    }
};
