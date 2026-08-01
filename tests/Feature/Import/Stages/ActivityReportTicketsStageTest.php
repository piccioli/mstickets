<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Import\Enums\ImportRunStatus;
use App\Import\Models\ImportRun;
use App\Import\Stages\ActivityReportTicketsStage;
use App\Import\Stages\ImportContext;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Import\Fixtures\InteractsWithLegacyDatabase;

uses(RefreshDatabase::class, InteractsWithLegacyDatabase::class);

function activityReportTicketsStageContext(bool $dryRun = false, ?int $limit = null): ImportContext
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

    Schema::connection('legacy')->create('activity_report_story', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('activity_report_id');
        $table->unsignedBigInteger('story_id');
        $table->timestamp('created_at')->nullable();
        $table->timestamp('updated_at')->nullable();
    });
});

function insertLegacyActivityReportStory(int $activityReportId, int $storyId): void
{
    DB::connection('legacy')->table('activity_report_story')->insert([
        'activity_report_id' => $activityReportId,
        'story_id' => $storyId,
        'created_at' => '2026-03-01 10:00:00',
        'updated_at' => '2026-03-01 10:00:00',
    ]);
}

function createV2ActivityReport(int $id): void
{
    $owner = User::factory()->create();

    DB::table('activity_reports')->insert([
        'id' => $id,
        'owner_kind' => 'user',
        'owner_user_id' => $owner->id,
        'owner_organization_id' => null,
        'period_type' => 'monthly',
        'year' => 2026,
        'month' => 3,
        'locale' => 'it',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function createV2TicketForActivityReports(int $id): void
{
    DB::table('tickets')->insert([
        'id' => $id,
        'title' => "Ticket {$id}",
        'status' => 'new',
        'status_changed_at' => now(),
        'type' => 'helpdesk',
        'priority' => 'low',
        'worked_minutes' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

test('imports the v1 activity_report<->story pivot into v2 as activity_report_ticket', function (): void {
    createV2ActivityReport(1);
    createV2TicketForActivityReports(10);
    insertLegacyActivityReportStory(1, 10);

    $result = (new ActivityReportTicketsStage)->run(activityReportTicketsStageContext());

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(1)
        ->and($result->skipped)->toBe(0);

    expect(DB::table('activity_report_ticket')
        ->where('activity_report_id', 1)
        ->where('ticket_id', 10)
        ->exists())->toBeTrue();
});

test('an association referencing a non-existent activity report is skipped and reported', function (): void {
    createV2TicketForActivityReports(10);
    insertLegacyActivityReportStory(999, 10);

    $result = (new ActivityReportTicketsStage)->run(activityReportTicketsStageContext());

    expect($result->skipped)->toBe(1)
        ->and($result->created)->toBe(0)
        ->and($result->warnings)->not->toBeEmpty();
});

test('an association referencing a non-existent ticket is skipped and reported', function (): void {
    createV2ActivityReport(1);
    insertLegacyActivityReportStory(1, 999);

    $result = (new ActivityReportTicketsStage)->run(activityReportTicketsStageContext());

    expect($result->skipped)->toBe(1)
        ->and($result->created)->toBe(0)
        ->and($result->warnings)->not->toBeEmpty();
});

test('dry-run does not write any row to the destination pivot table', function (): void {
    createV2ActivityReport(1);
    createV2TicketForActivityReports(10);
    insertLegacyActivityReportStory(1, 10);

    $result = (new ActivityReportTicketsStage)->run(activityReportTicketsStageContext(dryRun: true));

    expect($result->read)->toBe(1)
        ->and(DB::table('activity_report_ticket')->count())->toBe(0);
});

test('re-running the stage on the same dump is idempotent: second run only skips', function (): void {
    createV2ActivityReport(1);
    createV2TicketForActivityReports(10);
    createV2TicketForActivityReports(11);
    insertLegacyActivityReportStory(1, 10);
    insertLegacyActivityReportStory(1, 11);

    $stage = new ActivityReportTicketsStage;
    $first = $stage->run(activityReportTicketsStageContext());
    $second = $stage->run(activityReportTicketsStageContext());

    expect($first->created)->toBe(2)
        ->and($second->created)->toBe(0)
        ->and($second->skipped)->toBe(2)
        ->and(DB::table('activity_report_ticket')->count())->toBe(2);
});

test('--limit caps the number of v1 rows read', function (): void {
    createV2ActivityReport(1);
    createV2TicketForActivityReports(10);
    createV2TicketForActivityReports(11);
    insertLegacyActivityReportStory(1, 10);
    insertLegacyActivityReportStory(1, 11);

    $result = (new ActivityReportTicketsStage)->run(activityReportTicketsStageContext(limit: 1));

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(1);
});
