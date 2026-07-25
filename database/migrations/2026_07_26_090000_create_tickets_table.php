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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('tickets')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('new');
            $table->string('previous_status', 20)->nullable();
            $table->timestamp('status_changed_at');
            $table->string('type', 20)->default('helpdesk');
            $table->string('priority', 10)->default('low');
            $table->foreignId('requester_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('tester_id')->nullable()->constrained('users')->nullOnDelete();
            // fundraising_projects non esiste ancora (arriva in US-015, §5.2 Fundraising):
            // colonna senza vincolo FK per ora, da completare come FK quando quella story
            // introduce la tabella (stesso pattern di ticket_messages.email_message_id).
            $table->unsignedBigInteger('fundraising_project_id')->nullable();
            $table->text('waiting_reason')->nullable();
            $table->text('problem_reason')->nullable();
            $table->decimal('estimated_hours', 6, 2)->nullable();
            $table->unsignedInteger('worked_minutes')->default(0);
            $table->string('staging_url')->nullable();
            $table->string('production_url')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('parent_id');
            $table->index(['assignee_id', 'status']);
            $table->index(['requester_id', 'status']);
            $table->index(['tester_id', 'status']);
            $table->index(['status', 'status_changed_at']);
            $table->index('done_at');
            $table->index('fundraising_project_id');
        });

        // Indice full-text su title: sintassi specifica Postgres (to_tsvector), non
        // riproducibile su sqlite (usato dai test, vedi phpunit.xml) - guardia esplicita
        // sul driver invece di un'istruzione che fallirebbe in ambiente di test.
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement(
                "create index tickets_title_fulltext_index on tickets using gin (to_tsvector('italian', title))"
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
