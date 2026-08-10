<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Import\Enums\ImportRunStatus;
use App\Import\Models\ImportRun;
use App\Import\Stages\FundraisingProjectsStage;
use App\Import\Stages\ImportContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Import\Fixtures\InteractsWithLegacyDatabase;

uses(RefreshDatabase::class, InteractsWithLegacyDatabase::class);

function fundraisingProjectsStageContext(bool $dryRun = false, ?int $limit = null): ImportContext
{
    $importRun = ImportRun::create([
        'started_at' => now(),
        'dump_label' => 'test-dump',
        'stages' => [],
        'status' => ImportRunStatus::Running,
        'is_dry_run' => $dryRun,
    ]);

    return new ImportContext(importRun: $importRun, dryRun: $dryRun, limit: $limit);
}

beforeEach(function (): void {
    $this->useSqliteLegacyConnection();

    Schema::connection('legacy')->create('fundraising_projects', function (Blueprint $table): void {
        $table->id();
        $table->string('title');
        $table->unsignedBigInteger('fundraising_opportunity_id');
        $table->unsignedBigInteger('lead_user_id');
        $table->unsignedBigInteger('created_by');
        $table->unsignedBigInteger('responsible_user_id');
        $table->text('description')->nullable();
        $table->string('status')->default('draft');
        $table->decimal('requested_amount', 15, 2)->nullable();
        $table->decimal('approved_amount', 15, 2)->nullable();
        $table->date('submission_date')->nullable();
        $table->date('decision_date')->nullable();
        $table->timestamp('created_at')->nullable();
        $table->timestamp('updated_at')->nullable();
    });
});

function createV2FundraisingOpportunityForProjects(int $id, int $userId): void
{
    DB::table('fundraising_opportunities')->insert([
        'id' => $id,
        'name' => "Bando {$id}",
        'deadline' => '2026-12-31',
        'territorial_scope' => 'national',
        'created_by' => $userId,
        'responsible_user_id' => $userId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function insertLegacyFundraisingProject(array $attributes = []): void
{
    DB::connection('legacy')->table('fundraising_projects')->insert(array_merge([
        'id' => 1,
        'title' => 'Progetto rifugio alpino',
        'fundraising_opportunity_id' => 1,
        'lead_user_id' => 1,
        'created_by' => 1,
        'responsible_user_id' => 1,
        'description' => 'Descrizione progetto',
        'status' => 'submitted',
        'requested_amount' => '30000.00',
        'approved_amount' => null,
        'submission_date' => '2026-02-01',
        'decision_date' => null,
        'created_at' => '2026-01-01 10:00:00',
        'updated_at' => '2026-01-01 10:00:00',
    ], $attributes));
}

test('imports a v1 fundraising project into v2 with the id preserved and columns mapped', function (): void {
    User::factory()->create(['id' => 1]);
    createV2FundraisingOpportunityForProjects(1, 1);
    insertLegacyFundraisingProject(['id' => 5, 'title' => 'Progetto rifugio alpino']);

    $result = (new FundraisingProjectsStage)->run(fundraisingProjectsStageContext());

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(1)
        ->and($result->updated)->toBe(0)
        ->and($result->skipped)->toBe(0);

    $project = DB::table('fundraising_projects')->where('id', 5)->first();

    expect($project)->not->toBeNull()
        ->and($project->title)->toBe('Progetto rifugio alpino')
        ->and((int) $project->fundraising_opportunity_id)->toBe(1)
        ->and((int) $project->lead_user_id)->toBe(1)
        ->and((int) $project->created_by)->toBe(1)
        ->and((int) $project->responsible_user_id)->toBe(1)
        ->and($project->status)->toBe('submitted')
        ->and($project->submitted_at)->toBe('2026-02-01')
        ->and($project->decided_at)->toBeNull();
});

test('a project whose fundraising_opportunity_id does not exist in v2 is skipped and reported, not crashed', function (): void {
    User::factory()->create(['id' => 1]);
    insertLegacyFundraisingProject(['id' => 1, 'fundraising_opportunity_id' => 999]);

    $result = (new FundraisingProjectsStage)->run(fundraisingProjectsStageContext());

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(0)
        ->and($result->skipped)->toBe(1)
        ->and($result->warnings)->toContain('1 progetti fundraising v1 scartati: fundraising_opportunity_id inesistente in v2.')
        ->and(DB::table('fundraising_projects')->count())->toBe(0);
});

test('a project whose created_by does not exist in v2 is skipped and reported, not crashed', function (): void {
    User::factory()->create(['id' => 1]);
    createV2FundraisingOpportunityForProjects(1, 1);
    insertLegacyFundraisingProject(['id' => 1, 'created_by' => 999]);

    $result = (new FundraisingProjectsStage)->run(fundraisingProjectsStageContext());

    expect($result->created)->toBe(0)
        ->and($result->skipped)->toBe(1)
        ->and($result->warnings)->toContain('1 progetti fundraising v1 scartati: created_by inesistente in v2.')
        ->and(DB::table('fundraising_projects')->count())->toBe(0);
});

test('a project whose lead_user_id/responsible_user_id do not exist in v2 are nulled, not skipped', function (): void {
    User::factory()->create(['id' => 1]);
    createV2FundraisingOpportunityForProjects(1, 1);
    insertLegacyFundraisingProject(['id' => 1, 'lead_user_id' => 998, 'responsible_user_id' => 999]);

    $result = (new FundraisingProjectsStage)->run(fundraisingProjectsStageContext());

    expect($result->created)->toBe(1)
        ->and($result->skipped)->toBe(0)
        ->and($result->warnings)->toContain('1 progetti fundraising v1 con lead_user_id inesistente in v2: azzerato.')
        ->and($result->warnings)->toContain('1 progetti fundraising v1 con responsible_user_id inesistente in v2: azzerato.');

    $project = DB::table('fundraising_projects')->where('id', 1)->first();

    expect($project->lead_user_id)->toBeNull()
        ->and($project->responsible_user_id)->toBeNull();
});

test('dry-run does not write any row to the destination fundraising_projects table', function (): void {
    User::factory()->create(['id' => 1]);
    createV2FundraisingOpportunityForProjects(1, 1);
    insertLegacyFundraisingProject(['id' => 1]);

    $before = DB::table('fundraising_projects')->count();

    $result = (new FundraisingProjectsStage)->run(fundraisingProjectsStageContext(dryRun: true));

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(0)
        ->and(DB::table('fundraising_projects')->count())->toBe($before);
});

test('re-running the stage on the same dump is idempotent: second run only skips', function (): void {
    User::factory()->create(['id' => 1]);
    createV2FundraisingOpportunityForProjects(1, 1);
    insertLegacyFundraisingProject(['id' => 1]);
    insertLegacyFundraisingProject(['id' => 2, 'title' => 'Progetto europeo']);

    $stage = new FundraisingProjectsStage;
    $first = $stage->run(fundraisingProjectsStageContext());
    $second = $stage->run(fundraisingProjectsStageContext());

    expect($first->created)->toBe(2)
        ->and($second->created)->toBe(0)
        ->and($second->updated)->toBe(0)
        ->and($second->skipped)->toBe(2)
        ->and(DB::table('fundraising_projects')->count())->toBe(2);
});

test('a changed v1 row is applied as an update, not a duplicate insert', function (): void {
    User::factory()->create(['id' => 1]);
    createV2FundraisingOpportunityForProjects(1, 1);
    insertLegacyFundraisingProject(['id' => 1, 'title' => 'Progetto rifugio alpino']);
    (new FundraisingProjectsStage)->run(fundraisingProjectsStageContext());

    DB::connection('legacy')->table('fundraising_projects')->where('id', 1)->update(['title' => 'Progetto rifugio alpino (rivisto)']);
    $result = (new FundraisingProjectsStage)->run(fundraisingProjectsStageContext());

    expect($result->created)->toBe(0)
        ->and($result->updated)->toBe(1)
        ->and($result->skipped)->toBe(0)
        ->and(DB::table('fundraising_projects')->count())->toBe(1)
        ->and(DB::table('fundraising_projects')->where('id', 1)->value('title'))->toBe('Progetto rifugio alpino (rivisto)');
});

test('--limit caps the number of v1 rows read', function (): void {
    User::factory()->create(['id' => 1]);
    createV2FundraisingOpportunityForProjects(1, 1);
    insertLegacyFundraisingProject(['id' => 1]);
    insertLegacyFundraisingProject(['id' => 2, 'title' => 'Progetto europeo']);

    $result = (new FundraisingProjectsStage)->run(fundraisingProjectsStageContext(limit: 1));

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(1);
});
