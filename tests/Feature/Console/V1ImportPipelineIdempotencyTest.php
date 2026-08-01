<?php

declare(strict_types=1);

use App\Import\Enums\ImportRunStatus;
use App\Import\Models\ImportRun;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Import\Fixtures\InteractsWithLegacyDatabase;

uses(RefreshDatabase::class, InteractsWithLegacyDatabase::class);

/**
 * Verifica esplicita di idempotenza richiesta dall'AC di US-216 (v1:validate,
 * §11.7 del PRD): esegue `v1:import` (nessun filtro --stage/--from-stage, quindi
 * TUTTI gli stage registrati in config('import.stages')) due volte consecutive
 * sullo stesso dump v1 e verifica che la seconda esecuzione produca zero righe
 * create/aggiornate (solo "saltate") su ogni stage. Copre l'intera pipeline reale,
 * non solo il singolo stage isolato già testato da ciascuna *StageTest dedicata.
 */
beforeEach(function (): void {
    $this->useSqliteLegacyConnection();
    $this->seed(RolePermissionSeeder::class);
    seedFullLegacyFixture();
});

function seedFullLegacyFixture(): void
{
    Schema::connection('legacy')->create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->string('remember_token')->nullable();
        $table->string('activity_report_language')->nullable();
        $table->string('google_drive_url')->nullable();
        $table->string('google_drive_budget_url')->nullable();
        $table->string('roles')->nullable();
        $table->timestamps();
    });

    Schema::connection('legacy')->create('organizations', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('activity_report_language')->nullable();
        $table->timestamps();
    });

    Schema::connection('legacy')->create('organization_user', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('organization_id');
        $table->unsignedBigInteger('user_id');
        $table->timestamps();
    });

    Schema::connection('legacy')->create('documentations', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->text('description')->nullable();
        $table->string('category')->default('customer');
        $table->timestamps();
    });

    Schema::connection('legacy')->create('tags', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->text('description')->nullable();
        $table->decimal('estimate', 5, 2)->nullable();
        $table->unsignedBigInteger('taggable_id')->nullable();
        $table->string('taggable_type')->nullable();
        $table->timestamps();
    });

    Schema::connection('legacy')->create('stories', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->text('description')->nullable();
        $table->string('status')->default('new');
        $table->string('type')->nullable();
        $table->integer('priority')->default(1);
        $table->unsignedBigInteger('user_id')->nullable();
        $table->unsignedBigInteger('creator_id')->nullable();
        $table->unsignedBigInteger('tester_id')->nullable();
        $table->string('test_dev')->nullable();
        $table->string('test_prod')->nullable();
        $table->decimal('estimated_hours', 5, 2)->nullable();
        $table->unsignedBigInteger('fundraising_project_id')->nullable();
        $table->text('waiting_reason')->nullable();
        $table->text('problem_reason')->nullable();
        $table->timestamp('released_at')->nullable();
        $table->timestamp('done_at')->nullable();
        $table->unsignedBigInteger('parent_id')->nullable();
        $table->text('customer_request')->nullable();
        $table->float('hours')->nullable();
        $table->timestamps();
    });

    Schema::connection('legacy')->create('story_story', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('parent_id');
        $table->unsignedBigInteger('child_id');
    });

    Schema::connection('legacy')->create('story_participants', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('story_id');
        $table->unsignedBigInteger('user_id');
        $table->timestamps();
    });

    Schema::connection('legacy')->create('story_logs', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('story_id');
        $table->unsignedBigInteger('user_id')->nullable();
        $table->timestamp('viewed_at');
        $table->text('changes')->nullable();
        $table->timestamps();
    });

    Schema::connection('legacy')->create('taggables', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('tag_id');
        $table->unsignedBigInteger('taggable_id');
        $table->string('taggable_type');
        $table->timestamps();
    });

    Schema::connection('legacy')->create('media', function (Blueprint $table): void {
        $table->id();
        $table->string('model_type')->nullable();
        $table->unsignedBigInteger('model_id')->nullable();
        $table->string('uuid')->nullable();
        $table->string('name')->nullable();
        $table->string('file_name')->nullable();
        $table->string('mime_type')->nullable();
    });

    Schema::connection('legacy')->create('activity_reports', function (Blueprint $table): void {
        $table->id();
        $table->string('owner_type')->default('customer');
        $table->unsignedBigInteger('customer_id')->nullable();
        $table->unsignedBigInteger('organization_id')->nullable();
        $table->string('report_type')->default('monthly');
        $table->integer('year');
        $table->integer('month')->nullable();
        $table->timestamps();
    });

    Schema::connection('legacy')->create('activity_report_story', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('activity_report_id');
        $table->unsignedBigInteger('story_id');
        $table->timestamps();
    });

    Schema::connection('legacy')->create('fundraising_opportunities', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('official_url')->nullable();
        $table->decimal('endowment_fund', 15, 2)->nullable();
        $table->date('deadline');
        $table->string('program_name')->nullable();
        $table->string('sponsor')->nullable();
        $table->decimal('cofinancing_quota', 5, 2)->nullable();
        $table->decimal('max_contribution', 15, 2)->nullable();
        $table->string('territorial_scope')->default('national');
        $table->text('beneficiary_requirements')->nullable();
        $table->text('lead_requirements')->nullable();
        $table->unsignedBigInteger('created_by');
        $table->unsignedBigInteger('responsible_user_id');
        $table->timestamps();
    });

    Schema::connection('legacy')->create('fundraising_projects', function (Blueprint $table): void {
        $table->id();
        $table->string('title');
        $table->unsignedBigInteger('fundraising_opportunity_id');
        $table->unsignedBigInteger('lead_user_id')->nullable();
        $table->unsignedBigInteger('created_by');
        $table->unsignedBigInteger('responsible_user_id')->nullable();
        $table->text('description')->nullable();
        $table->string('status')->default('draft');
        $table->decimal('requested_amount', 15, 2)->nullable();
        $table->decimal('approved_amount', 15, 2)->nullable();
        $table->date('submission_date')->nullable();
        $table->date('decision_date')->nullable();
        $table->timestamps();
    });

    Schema::connection('legacy')->create('fundraising_project_partners', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('fundraising_project_id');
        $table->unsignedBigInteger('user_id');
        $table->timestamps();
    });

    $now = '2026-01-01 09:00:00';

    DB::connection('legacy')->table('users')->insert([
        ['id' => 1, 'name' => 'Utente Uno', 'email' => 'uno@example.test', 'password' => 'secret', 'activity_report_language' => 'it', 'roles' => null, 'created_at' => $now, 'updated_at' => $now],
        ['id' => 2, 'name' => 'Utente Due', 'email' => 'due@example.test', 'password' => 'secret', 'activity_report_language' => 'it', 'roles' => json_encode(['customer']), 'created_at' => $now, 'updated_at' => $now],
    ]);

    DB::connection('legacy')->table('organizations')->insert([
        'id' => 1, 'name' => 'ACME', 'activity_report_language' => 'it', 'created_at' => $now, 'updated_at' => $now,
    ]);

    DB::connection('legacy')->table('organization_user')->insert([
        'organization_id' => 1, 'user_id' => 1, 'created_at' => $now, 'updated_at' => $now,
    ]);

    DB::connection('legacy')->table('documentations')->insert([
        'id' => 1, 'name' => 'Guida', 'description' => 'Una guida', 'category' => 'customer', 'created_at' => $now, 'updated_at' => $now,
    ]);

    DB::connection('legacy')->table('tags')->insert([
        'id' => 1, 'name' => 'urgente', 'created_at' => $now, 'updated_at' => $now,
    ]);

    DB::connection('legacy')->table('stories')->insert([
        ['id' => 1, 'name' => 'Ticket padre', 'status' => 'done', 'type' => 'bug', 'priority' => 1, 'user_id' => 1, 'creator_id' => 1, 'tester_id' => null, 'parent_id' => null, 'hours' => 2.0, 'created_at' => $now, 'updated_at' => $now],
        ['id' => 2, 'name' => 'Ticket figlio', 'status' => 'new', 'type' => 'feature', 'priority' => 2, 'user_id' => 1, 'creator_id' => 2, 'tester_id' => null, 'parent_id' => 1, 'hours' => null, 'created_at' => $now, 'updated_at' => $now],
    ]);

    DB::connection('legacy')->table('story_participants')->insert([
        'story_id' => 2, 'user_id' => 2, 'created_at' => $now, 'updated_at' => $now,
    ]);

    DB::connection('legacy')->table('story_logs')->insert([
        ['story_id' => 1, 'user_id' => 1, 'viewed_at' => '2026-01-01 09:00:00', 'changes' => json_encode(['status' => 'progress']), 'created_at' => $now, 'updated_at' => $now],
        ['story_id' => 1, 'user_id' => 1, 'viewed_at' => '2026-01-01 10:00:00', 'changes' => json_encode(['status' => 'done']), 'created_at' => $now, 'updated_at' => $now],
        ['story_id' => 1, 'user_id' => 2, 'viewed_at' => '2026-01-01 11:00:00', 'changes' => json_encode(['watch' => '2026-01-01 11:00:00']), 'created_at' => $now, 'updated_at' => $now],
    ]);

    DB::connection('legacy')->table('taggables')->insert([
        'tag_id' => 1, 'taggable_id' => 1, 'taggable_type' => 'App\\Models\\Story', 'created_at' => $now, 'updated_at' => $now,
    ]);

    DB::connection('legacy')->table('activity_reports')->insert([
        'id' => 1, 'owner_type' => 'customer', 'customer_id' => 1, 'organization_id' => null, 'report_type' => 'monthly', 'year' => 2026, 'month' => 1, 'created_at' => $now, 'updated_at' => $now,
    ]);

    DB::connection('legacy')->table('activity_report_story')->insert([
        'activity_report_id' => 1, 'story_id' => 1, 'created_at' => $now, 'updated_at' => $now,
    ]);

    DB::connection('legacy')->table('fundraising_opportunities')->insert([
        'id' => 1, 'name' => 'Bando test', 'deadline' => '2026-06-01', 'territorial_scope' => 'national', 'created_by' => 1, 'responsible_user_id' => 1, 'created_at' => $now, 'updated_at' => $now,
    ]);

    DB::connection('legacy')->table('fundraising_projects')->insert([
        'id' => 1, 'title' => 'Progetto test', 'fundraising_opportunity_id' => 1, 'lead_user_id' => 1, 'created_by' => 1, 'responsible_user_id' => 1, 'status' => 'draft', 'created_at' => $now, 'updated_at' => $now,
    ]);

    DB::connection('legacy')->table('fundraising_project_partners')->insert([
        'fundraising_project_id' => 1, 'user_id' => 1, 'created_at' => $now, 'updated_at' => $now,
    ]);
}

test('a second consecutive v1:import run creates/updates nothing on every registered stage', function (): void {
    Artisan::call('v1:import');
    Artisan::call('v1:import');

    $runs = ImportRun::query()->orderBy('id')->get();

    expect($runs)->toHaveCount(2);

    $secondRun = $runs->last();

    expect($secondRun->status)->toBe(ImportRunStatus::Completed);

    /** @var array<string, array{read:int, created:int, updated:int, skipped:int, warnings:array<int,string>}> $stages */
    $stages = $secondRun->stages;

    expect($stages)->not->toBeEmpty();

    foreach (config('import.stages') as $stageClass) {
        $stageName = (new $stageClass)->name();

        expect($stages)->toHaveKey($stageName);
        expect($stages[$stageName]['created'])
            ->toBe(0, "Lo stage \"{$stageName}\" ha creato {$stages[$stageName]['created']} righe alla seconda esecuzione consecutiva: non idempotente.");
        expect($stages[$stageName]['updated'])
            ->toBe(0, "Lo stage \"{$stageName}\" ha aggiornato {$stages[$stageName]['updated']} righe alla seconda esecuzione consecutiva: non idempotente.");
    }
});
