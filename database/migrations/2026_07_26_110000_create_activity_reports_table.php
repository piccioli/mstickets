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
        Schema::create('activity_reports', function (Blueprint $table) {
            $table->id();
            $table->string('owner_kind', 15);
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('owner_organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
            $table->string('period_type', 10);
            $table->unsignedSmallInteger('year');
            $table->unsignedSmallInteger('month')->nullable();
            $table->string('locale', 5);
            $table->string('pdf_path')->nullable();
            $table->timestamp('pdf_generated_at')->nullable();
            $table->timestamps();
        });

        // U(owner_kind, owner_user_id, owner_organization_id, period_type, year, month) (§5.2): un
        // unique() standard su queste colonne non basterebbe, perché owner_user_id/owner_organization_id
        // sono sempre NULL per uno dei due owner_kind e sia Postgres sia sqlite trattano NULL come
        // distinto da se stesso nei vincoli unique (righe duplicate passerebbero indisturbate). Stesso
        // pattern già usato per l'indice funzionale case-insensitive su users.email (US-010): un indice
        // unique su espressione con coalesce(), portabile identico su entrambi i driver.
        DB::statement(
            'create unique index activity_reports_owner_period_unique on activity_reports '.
            '(owner_kind, coalesce(owner_user_id, 0), coalesce(owner_organization_id, 0), period_type, year, coalesce(month, 0))'
        );

        // Vincolo CHECK "esattamente uno tra owner_user_id/owner_organization_id valorizzato,
        // coerente con owner_kind" (§5.2): Blueprint non ha un metodo `check()` nativo per
        // nessun driver (solo l'enum-column "varchar check (col in (...))" interno). Sqlite
        // (usato dai test) non supporta `ALTER TABLE ... ADD CONSTRAINT`, quindi il vincolo va
        // emulato con due trigger BEFORE INSERT/UPDATE che sollevano RAISE(ABORT, ...): stessa
        // violazione osservabile lato applicativo (QueryException) di un vero CHECK Postgres.
        $expression = <<<'SQL'
            (owner_kind = 'user' AND owner_user_id IS NOT NULL AND owner_organization_id IS NULL)
            OR (owner_kind = 'organization' AND owner_organization_id IS NOT NULL AND owner_user_id IS NULL)
            SQL;

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement("alter table activity_reports add constraint activity_reports_owner_check check ({$expression})");
        } elseif (Schema::getConnection()->getDriverName() === 'sqlite') {
            $newExpression = str_replace(['owner_kind', 'owner_user_id', 'owner_organization_id'], ['NEW.owner_kind', 'NEW.owner_user_id', 'NEW.owner_organization_id'], $expression);

            foreach (['insert', 'update'] as $event) {
                DB::statement(<<<SQL
                    create trigger activity_reports_owner_check_{$event}
                    before {$event} on activity_reports
                    when not ({$newExpression})
                    begin
                        select raise(abort, 'activity_reports_owner_check violated');
                    end
                    SQL);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_reports');
    }
};
