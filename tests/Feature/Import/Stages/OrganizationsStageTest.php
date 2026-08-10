<?php

declare(strict_types=1);

use App\Import\Enums\ImportRunStatus;
use App\Import\Models\ImportRun;
use App\Import\Stages\ImportContext;
use App\Import\Stages\OrganizationsStage;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Import\Fixtures\InteractsWithLegacyDatabase;

uses(RefreshDatabase::class, InteractsWithLegacyDatabase::class);

function organizationsStageContext(bool $dryRun = false, ?int $limit = null): ImportContext
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

    Schema::connection('legacy')->create('organizations', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('activity_report_language', 2)->default('it');
        $table->timestamp('created_at')->nullable();
        $table->timestamp('updated_at')->nullable();
    });
});

function insertLegacyOrganization(array $attributes = []): void
{
    DB::connection('legacy')->table('organizations')->insert(array_merge([
        'name' => 'ACME S.r.l.',
        'activity_report_language' => 'it',
        'created_at' => '2026-01-01 10:00:00',
        'updated_at' => '2026-01-01 10:00:00',
    ], $attributes));
}

test('imports v1 organizations into v2 with the id preserved and columns mapped', function (): void {
    insertLegacyOrganization([
        'id' => 7,
        'name' => 'ACME S.r.l.',
        'activity_report_language' => 'en',
    ]);

    $result = (new OrganizationsStage)->run(organizationsStageContext());

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(1)
        ->and($result->updated)->toBe(0)
        ->and($result->skipped)->toBe(0);

    $organization = DB::table('organizations')->where('id', 7)->first();

    expect($organization)->not->toBeNull()
        ->and($organization->name)->toBe('ACME S.r.l.')
        ->and($organization->locale)->toBe('en');
});

test('dry-run does not write any row to the destination organizations table', function (): void {
    insertLegacyOrganization(['id' => 1]);

    $before = DB::table('organizations')->count();

    $result = (new OrganizationsStage)->run(organizationsStageContext(dryRun: true));

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(0)
        ->and(DB::table('organizations')->count())->toBe($before);
});

test('re-running the stage on the same dump is idempotent: second run only skips', function (): void {
    insertLegacyOrganization(['id' => 1]);
    insertLegacyOrganization(['id' => 2, 'name' => 'Beta S.p.A.']);

    $stage = new OrganizationsStage;
    $first = $stage->run(organizationsStageContext());
    $second = $stage->run(organizationsStageContext());

    expect($first->created)->toBe(2)
        ->and($second->created)->toBe(0)
        ->and($second->updated)->toBe(0)
        ->and($second->skipped)->toBe(2)
        ->and(DB::table('organizations')->count())->toBe(2);
});

test('a changed v1 row is applied as an update, not a duplicate insert', function (): void {
    insertLegacyOrganization(['id' => 1, 'name' => 'ACME S.r.l.']);
    (new OrganizationsStage)->run(organizationsStageContext());

    DB::connection('legacy')->table('organizations')->where('id', 1)->update(['name' => 'ACME S.p.A.']);
    $result = (new OrganizationsStage)->run(organizationsStageContext());

    expect($result->created)->toBe(0)
        ->and($result->updated)->toBe(1)
        ->and($result->skipped)->toBe(0)
        ->and(DB::table('organizations')->count())->toBe(1)
        ->and(DB::table('organizations')->where('id', 1)->value('name'))->toBe('ACME S.p.A.');
});

test('--limit caps the number of v1 rows read', function (): void {
    insertLegacyOrganization(['id' => 1]);
    insertLegacyOrganization(['id' => 2, 'name' => 'Beta S.p.A.']);

    $result = (new OrganizationsStage)->run(organizationsStageContext(limit: 1));

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(1);
});
