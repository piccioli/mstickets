<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ticket_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 30);
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20)->nullable();
            $table->jsonb('changes')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['ticket_id', 'occurred_at']);
            $table->index(['user_id', 'occurred_at']);
            $table->index(['to_status', 'occurred_at']);
            $table->index(['event', 'occurred_at']);
        });

        // GIN su jsonb: solo Postgres (sqlite, usato dai test, non ha un tipo jsonb nativo
        // né supporta indici GIN - vedi guardia analoga sull'indice full-text di tickets).
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('create index ticket_logs_changes_gin_index on ticket_logs using gin (changes)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_logs');
    }
};
