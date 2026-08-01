<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Import\Enums\ImportRunStatus;
use App\Import\Models\ImportRun;
use App\Import\Stages\FundraisingPartnersStage;
use App\Import\Stages\ImportContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Import\Fixtures\InteractsWithLegacyDatabase;

uses(RefreshDatabase::class, InteractsWithLegacyDatabase::class);

function fundraisingPartnersStageContext(bool $dryRun = false, ?int $limit = null): ImportContext
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

    Schema::connection('legacy')->create('fundraising_project_partners', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('fundraising_project_id');
        $table->unsignedBigInteger('user_id');
        $table->timestamp('created_at')->nullable();
        $table->timestamp('updated_at')->nullable();
    });
});

function insertLegacyFundraisingProjectPartner(int $fundraisingProjectId, int $userId): void
{
    DB::connection('legacy')->table('fundraising_project_partners')->insert([
        'fundraising_project_id' => $fundraisingProjectId,
        'user_id' => $userId,
        'created_at' => '2026-01-01 10:00:00',
        'updated_at' => '2026-01-01 10:00:00',
    ]);
}

function createV2FundraisingProjectForPartners(int $id): void
{
    $staffUserId = User::factory()->create(['id' => 100 + $id])->id;

    DB::table('fundraising_opportunities')->insert([
        'id' => $id,
        'name' => "Bando {$id}",
        'deadline' => '2026-12-31',
        'territorial_scope' => 'national',
        'created_by' => $staffUserId,
        'responsible_user_id' => $staffUserId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('fundraising_projects')->insert([
        'id' => $id,
        'title' => "Progetto {$id}",
        'fundraising_opportunity_id' => $id,
        'created_by' => $staffUserId,
        'status' => 'draft',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

test('imports the v1 fundraising project<->partner pivot into v2', function (): void {
    createV2FundraisingProjectForPartners(1);
    User::factory()->create(['id' => 1]);
    insertLegacyFundraisingProjectPartner(1, 1);

    $result = (new FundraisingPartnersStage)->run(fundraisingPartnersStageContext());

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(1)
        ->and($result->skipped)->toBe(0);

    expect(DB::table('fundraising_project_partners')->where('fundraising_project_id', 1)->where('user_id', 1)->exists())->toBeTrue();
});

test('dry-run does not write any pivot row', function (): void {
    createV2FundraisingProjectForPartners(1);
    User::factory()->create(['id' => 1]);
    insertLegacyFundraisingProjectPartner(1, 1);

    $result = (new FundraisingPartnersStage)->run(fundraisingPartnersStageContext(dryRun: true));

    expect($result->created)->toBe(0)
        ->and(DB::table('fundraising_project_partners')->count())->toBe(0);
});

test('re-running the stage on the same dump is idempotent: second run only skips', function (): void {
    createV2FundraisingProjectForPartners(1);
    User::factory()->create(['id' => 1]);
    User::factory()->create(['id' => 2]);
    insertLegacyFundraisingProjectPartner(1, 1);
    insertLegacyFundraisingProjectPartner(1, 2);

    $stage = new FundraisingPartnersStage;
    $first = $stage->run(fundraisingPartnersStageContext());
    $second = $stage->run(fundraisingPartnersStageContext());

    expect($first->created)->toBe(2)
        ->and($second->created)->toBe(0)
        ->and($second->skipped)->toBe(2)
        ->and(DB::table('fundraising_project_partners')->count())->toBe(2);
});

test('a partner referencing a non-existent v2 fundraising project is reported, not crashed', function (): void {
    User::factory()->create(['id' => 1]);
    insertLegacyFundraisingProjectPartner(999, 1);

    $result = (new FundraisingPartnersStage)->run(fundraisingPartnersStageContext());

    expect($result->created)->toBe(0)
        ->and($result->skipped)->toBe(1)
        ->and($result->warnings[0])->toContain('progetto fundraising inesistente')
        ->and(DB::table('fundraising_project_partners')->count())->toBe(0);
});

test('a partner referencing a non-existent v2 user is reported, not crashed', function (): void {
    createV2FundraisingProjectForPartners(1);
    insertLegacyFundraisingProjectPartner(1, 999);

    $result = (new FundraisingPartnersStage)->run(fundraisingPartnersStageContext());

    expect($result->created)->toBe(0)
        ->and($result->skipped)->toBe(1)
        ->and($result->warnings[0])->toContain('utente inesistente')
        ->and(DB::table('fundraising_project_partners')->count())->toBe(0);
});

test('--limit caps the number of v1 rows read', function (): void {
    createV2FundraisingProjectForPartners(1);
    User::factory()->create(['id' => 1]);
    User::factory()->create(['id' => 2]);
    insertLegacyFundraisingProjectPartner(1, 1);
    insertLegacyFundraisingProjectPartner(1, 2);

    $result = (new FundraisingPartnersStage)->run(fundraisingPartnersStageContext(limit: 1));

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(1);
});
