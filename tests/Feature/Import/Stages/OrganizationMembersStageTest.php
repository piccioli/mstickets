<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Import\Enums\ImportRunStatus;
use App\Import\Models\ImportRun;
use App\Import\Stages\ImportContext;
use App\Import\Stages\OrganizationMembersStage;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Import\Fixtures\InteractsWithLegacyDatabase;

uses(RefreshDatabase::class, InteractsWithLegacyDatabase::class);

function organizationMembersStageContext(bool $dryRun = false, ?int $limit = null): ImportContext
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

    Schema::connection('legacy')->create('organization_user', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('organization_id');
        $table->unsignedBigInteger('user_id');
        $table->timestamp('created_at')->nullable();
        $table->timestamp('updated_at')->nullable();
    });
});

function insertLegacyOrganizationMember(int $organizationId, int $userId): void
{
    DB::connection('legacy')->table('organization_user')->insert([
        'organization_id' => $organizationId,
        'user_id' => $userId,
        'created_at' => '2026-01-01 10:00:00',
        'updated_at' => '2026-01-01 10:00:00',
    ]);
}

function createV2Organization(int $id): void
{
    DB::table('organizations')->insert([
        'id' => $id,
        'name' => "Organization {$id}",
        'locale' => 'it',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

test('imports the v1 organization<->user pivot into v2', function (): void {
    createV2Organization(1);
    User::factory()->create(['id' => 1]);
    insertLegacyOrganizationMember(1, 1);

    $result = (new OrganizationMembersStage)->run(organizationMembersStageContext());

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(1)
        ->and($result->skipped)->toBe(0)
        ->and($result->warnings)->toBe([]);

    expect(DB::table('organization_user')->where('organization_id', 1)->where('user_id', 1)->exists())->toBeTrue();
});

test('dry-run does not write any pivot row', function (): void {
    createV2Organization(1);
    User::factory()->create(['id' => 1]);
    insertLegacyOrganizationMember(1, 1);

    $result = (new OrganizationMembersStage)->run(organizationMembersStageContext(dryRun: true));

    expect($result->created)->toBe(0)
        ->and(DB::table('organization_user')->count())->toBe(0);
});

test('re-running the stage on the same dump is idempotent: second run only skips', function (): void {
    createV2Organization(1);
    User::factory()->create(['id' => 1]);
    User::factory()->create(['id' => 2]);
    insertLegacyOrganizationMember(1, 1);
    insertLegacyOrganizationMember(1, 2);

    $stage = new OrganizationMembersStage;
    $first = $stage->run(organizationMembersStageContext());
    $second = $stage->run(organizationMembersStageContext());

    expect($first->created)->toBe(2)
        ->and($second->created)->toBe(0)
        ->and($second->skipped)->toBe(2)
        ->and(DB::table('organization_user')->count())->toBe(2);
});

test('a membership referencing a non-existent v2 organization is reported, not crashed', function (): void {
    User::factory()->create(['id' => 1]);
    insertLegacyOrganizationMember(999, 1);

    $result = (new OrganizationMembersStage)->run(organizationMembersStageContext());

    expect($result->created)->toBe(0)
        ->and($result->skipped)->toBe(1)
        ->and($result->warnings)->toHaveCount(1)
        ->and($result->warnings[0])->toContain('organizzazione inesistente')
        ->and(DB::table('organization_user')->count())->toBe(0);
});

test('a membership referencing a non-existent v2 user is reported, not crashed', function (): void {
    createV2Organization(1);
    insertLegacyOrganizationMember(1, 999);

    $result = (new OrganizationMembersStage)->run(organizationMembersStageContext());

    expect($result->created)->toBe(0)
        ->and($result->skipped)->toBe(1)
        ->and($result->warnings)->toHaveCount(1)
        ->and($result->warnings[0])->toContain('utente inesistente')
        ->and(DB::table('organization_user')->count())->toBe(0);
});

test('--limit caps the number of v1 rows read', function (): void {
    createV2Organization(1);
    User::factory()->create(['id' => 1]);
    User::factory()->create(['id' => 2]);
    insertLegacyOrganizationMember(1, 1);
    insertLegacyOrganizationMember(1, 2);

    $result = (new OrganizationMembersStage)->run(organizationMembersStageContext(limit: 1));

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(1);
});
