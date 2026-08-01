<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Import\Enums\ImportRunStatus;
use App\Import\Models\ImportRun;
use App\Import\Stages\ActivityReportsStage;
use App\Import\Stages\ImportContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Import\Fixtures\InteractsWithLegacyDatabase;

uses(RefreshDatabase::class, InteractsWithLegacyDatabase::class);

function activityReportsStageContext(bool $dryRun = false, ?int $limit = null): ImportContext
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

    Schema::connection('legacy')->create('activity_reports', function (Blueprint $table): void {
        $table->id();
        $table->string('owner_type')->default('customer');
        $table->unsignedBigInteger('customer_id')->nullable();
        $table->unsignedBigInteger('organization_id')->nullable();
        $table->string('report_type')->default('monthly');
        $table->integer('year');
        $table->integer('month')->nullable();
        $table->string('pdf_url')->nullable();
        $table->timestamp('created_at')->nullable();
        $table->timestamp('updated_at')->nullable();
    });
});

function insertLegacyActivityReport(array $attributes = []): void
{
    DB::connection('legacy')->table('activity_reports')->insert(array_merge([
        'id' => 1,
        'owner_type' => 'customer',
        'customer_id' => 1,
        'organization_id' => null,
        'report_type' => 'monthly',
        'year' => 2026,
        'month' => 3,
        'pdf_url' => 'https://old.example/report-1.pdf',
        'created_at' => '2026-03-01 10:00:00',
        'updated_at' => '2026-03-01 10:00:00',
    ], $attributes));
}

test('imports a v1 user-owned report into v2 with the id preserved and the owner locale derived', function (): void {
    User::factory()->create(['id' => 1, 'locale' => 'en']);
    insertLegacyActivityReport(['id' => 5, 'owner_type' => 'customer', 'customer_id' => 1, 'organization_id' => null]);

    $result = (new ActivityReportsStage)->run(activityReportsStageContext());

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(1)
        ->and($result->updated)->toBe(0)
        ->and($result->skipped)->toBe(0);

    $report = DB::table('activity_reports')->where('id', 5)->first();

    expect($report)->not->toBeNull()
        ->and($report->owner_kind)->toBe('user')
        ->and((int) $report->owner_user_id)->toBe(1)
        ->and($report->owner_organization_id)->toBeNull()
        ->and($report->period_type)->toBe('monthly')
        ->and((int) $report->year)->toBe(2026)
        ->and($report->locale)->toBe('en')
        ->and($report->pdf_path)->toBeNull()
        ->and($report->pdf_generated_at)->toBeNull();
});

test('imports a v1 organization-owned report into v2 with the organization locale derived', function (): void {
    DB::table('organizations')->insert([
        'id' => 9,
        'name' => 'ACME S.r.l.',
        'locale' => 'fr',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    insertLegacyActivityReport([
        'id' => 6,
        'owner_type' => 'organization',
        'customer_id' => null,
        'organization_id' => 9,
        'report_type' => 'annual',
        'month' => null,
    ]);

    $result = (new ActivityReportsStage)->run(activityReportsStageContext());

    expect($result->created)->toBe(1);

    $report = DB::table('activity_reports')->where('id', 6)->first();

    expect($report->owner_kind)->toBe('organization')
        ->and($report->owner_user_id)->toBeNull()
        ->and((int) $report->owner_organization_id)->toBe(9)
        ->and($report->period_type)->toBe('annual')
        ->and($report->locale)->toBe('fr');
});

test('a report whose owner user does not exist in v2 is skipped and reported, not crashed', function (): void {
    insertLegacyActivityReport(['id' => 1, 'owner_type' => 'customer', 'customer_id' => 999]);

    $result = (new ActivityReportsStage)->run(activityReportsStageContext());

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(0)
        ->and($result->skipped)->toBe(1)
        ->and($result->warnings)->not->toBeEmpty()
        ->and(DB::table('activity_reports')->count())->toBe(0);
});

test('an ambiguous v1 report (both customer_id and organization_id set) is skipped and reported, never violating the owner CHECK', function (): void {
    User::factory()->create(['id' => 1]);
    DB::table('organizations')->insert([
        'id' => 2,
        'name' => 'ACME S.r.l.',
        'locale' => 'it',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    insertLegacyActivityReport(['id' => 1, 'owner_type' => 'customer', 'customer_id' => 1, 'organization_id' => 2]);

    $result = (new ActivityReportsStage)->run(activityReportsStageContext());

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(0)
        ->and($result->skipped)->toBe(1)
        ->and($result->warnings)->not->toBeEmpty()
        ->and(DB::table('activity_reports')->count())->toBe(0);
});

test('an ambiguous v1 report (owner_type customer with no customer_id) is skipped and reported', function (): void {
    insertLegacyActivityReport(['id' => 1, 'owner_type' => 'customer', 'customer_id' => null, 'organization_id' => null]);

    $result = (new ActivityReportsStage)->run(activityReportsStageContext());

    expect($result->created)->toBe(0)
        ->and($result->skipped)->toBe(1)
        ->and($result->warnings)->not->toBeEmpty();
});

test('dry-run does not write any row to the destination activity_reports table', function (): void {
    User::factory()->create(['id' => 1]);
    insertLegacyActivityReport(['id' => 1]);

    $before = DB::table('activity_reports')->count();

    $result = (new ActivityReportsStage)->run(activityReportsStageContext(dryRun: true));

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(0)
        ->and(DB::table('activity_reports')->count())->toBe($before);
});

test('re-running the stage on the same dump is idempotent: second run only skips', function (): void {
    User::factory()->create(['id' => 1]);
    insertLegacyActivityReport(['id' => 1, 'customer_id' => 1]);
    insertLegacyActivityReport(['id' => 2, 'customer_id' => 1, 'month' => 4]);

    $stage = new ActivityReportsStage;
    $first = $stage->run(activityReportsStageContext());
    $second = $stage->run(activityReportsStageContext());

    expect($first->created)->toBe(2)
        ->and($second->created)->toBe(0)
        ->and($second->updated)->toBe(0)
        ->and($second->skipped)->toBe(2)
        ->and(DB::table('activity_reports')->count())->toBe(2);
});

test('a changed v1 row is applied as an update, not a duplicate insert', function (): void {
    User::factory()->create(['id' => 1]);
    insertLegacyActivityReport(['id' => 1, 'customer_id' => 1, 'month' => 3]);
    (new ActivityReportsStage)->run(activityReportsStageContext());

    DB::connection('legacy')->table('activity_reports')->where('id', 1)->update(['month' => 4]);
    $result = (new ActivityReportsStage)->run(activityReportsStageContext());

    expect($result->created)->toBe(0)
        ->and($result->updated)->toBe(1)
        ->and($result->skipped)->toBe(0)
        ->and(DB::table('activity_reports')->count())->toBe(1)
        ->and((int) DB::table('activity_reports')->where('id', 1)->value('month'))->toBe(4);
});

test('--limit caps the number of v1 rows read', function (): void {
    User::factory()->create(['id' => 1]);
    insertLegacyActivityReport(['id' => 1, 'customer_id' => 1]);
    insertLegacyActivityReport(['id' => 2, 'customer_id' => 1, 'month' => 4]);

    $result = (new ActivityReportsStage)->run(activityReportsStageContext(limit: 1));

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(1);
});
