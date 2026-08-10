<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Import\Enums\ImportRunStatus;
use App\Import\Models\ImportRun;
use App\Import\Stages\ImportContext;
use App\Import\Stages\TicketLogsStage;
use App\Import\Stages\TicketViewsStage;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Import\Fixtures\InteractsWithLegacyDatabase;

uses(RefreshDatabase::class, InteractsWithLegacyDatabase::class);

function ticketViewsStageContext(bool $dryRun = false, ?int $limit = null): ImportContext
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

    Schema::connection('legacy')->create('story_logs', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('story_id');
        $table->unsignedBigInteger('user_id')->nullable();
        $table->timestamp('viewed_at');
        $table->text('changes')->nullable();
        $table->timestamp('created_at')->nullable();
        $table->timestamp('updated_at')->nullable();
    });
});

function insertTicketForViews(int $id): void
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

function insertLegacyWatchLogRow(int $storyId, ?int $userId, string $viewedAt): void
{
    DB::connection('legacy')->table('story_logs')->insert([
        'story_id' => $storyId,
        'user_id' => $userId,
        'viewed_at' => $viewedAt,
        'changes' => json_encode(['watch' => $viewedAt]),
        'created_at' => $viewedAt,
        'updated_at' => $viewedAt,
    ]);
}

test('watch-only logs on the same day aggregate into a single ticket_views row', function (): void {
    $viewer = User::factory()->create();
    insertTicketForViews(1);
    insertLegacyWatchLogRow(1, $viewer->id, '2026-01-01 09:00:00');
    insertLegacyWatchLogRow(1, $viewer->id, '2026-01-01 14:30:00');
    insertLegacyWatchLogRow(1, $viewer->id, '2026-01-01 18:00:00');

    $result = (new TicketViewsStage)->run(ticketViewsStageContext());

    expect($result->read)->toBe(3)
        ->and($result->created)->toBe(1)
        ->and($result->skipped)->toBe(0)
        ->and($result->warnings)->toBe([]);

    $view = DB::table('ticket_views')->first();

    expect($view->ticket_id)->toBe(1)
        ->and($view->user_id)->toBe($viewer->id)
        ->and((string) $view->viewed_on)->toContain('2026-01-01')
        ->and((int) $view->view_count)->toBe(3)
        ->and((string) $view->last_viewed_at)->toContain('18:00:00');
});

test('watch-only logs on different days become separate ticket_views rows', function (): void {
    $viewer = User::factory()->create();
    insertTicketForViews(1);
    insertLegacyWatchLogRow(1, $viewer->id, '2026-01-01 09:00:00');
    insertLegacyWatchLogRow(1, $viewer->id, '2026-01-02 09:00:00');

    $result = (new TicketViewsStage)->run(ticketViewsStageContext());

    expect($result->created)->toBe(2)
        ->and(DB::table('ticket_views')->count())->toBe(2);
});

test('a watch log referencing a non-existent v2 ticket is discarded and reported, not crashed', function (): void {
    $viewer = User::factory()->create();
    insertLegacyWatchLogRow(999, $viewer->id, '2026-01-01 09:00:00');

    $result = (new TicketViewsStage)->run(ticketViewsStageContext());

    expect($result->skipped)->toBe(1)
        ->and($result->warnings)->toHaveCount(1)
        ->and($result->warnings[0])->toContain('ticket v1 inesistente in v2');

    expect(DB::table('ticket_views')->count())->toBe(0);
});

test('a watch log referencing a non-existent v2 user is discarded and reported, not crashed', function (): void {
    insertTicketForViews(1);
    insertLegacyWatchLogRow(1, 999999, '2026-01-01 09:00:00');

    $result = (new TicketViewsStage)->run(ticketViewsStageContext());

    expect($result->skipped)->toBe(1)
        ->and($result->warnings)->toHaveCount(1)
        ->and($result->warnings[0])->toContain('utente v1 inesistente in v2');

    expect(DB::table('ticket_views')->count())->toBe(0);
});

test('logs that are not watch-only are excluded and reported, not imported as a ticket_view', function (): void {
    $viewer = User::factory()->create();
    insertTicketForViews(1);

    DB::connection('legacy')->table('story_logs')->insert([
        'story_id' => 1,
        'user_id' => $viewer->id,
        'viewed_at' => '2026-01-01 09:00:00',
        'changes' => json_encode(['status' => 'assigned']),
        'created_at' => '2026-01-01 09:00:00',
        'updated_at' => '2026-01-01 09:00:00',
    ]);

    $result = (new TicketViewsStage)->run(ticketViewsStageContext());

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(0)
        ->and($result->skipped)->toBe(1)
        ->and($result->warnings)->toHaveCount(1)
        ->and($result->warnings[0])->toContain('non hanno sola chiave "watch"');

    expect(DB::table('ticket_views')->count())->toBe(0);
});

test('dry-run does not write any ticket_views row', function (): void {
    $viewer = User::factory()->create();
    insertTicketForViews(1);
    insertLegacyWatchLogRow(1, $viewer->id, '2026-01-01 09:00:00');

    $result = (new TicketViewsStage)->run(ticketViewsStageContext(dryRun: true));

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(0)
        ->and(DB::table('ticket_views')->count())->toBe(0);
});

test('--limit caps the number of source rows read', function (): void {
    $viewer = User::factory()->create();
    insertTicketForViews(1);
    insertLegacyWatchLogRow(1, $viewer->id, '2026-01-01 09:00:00');
    insertLegacyWatchLogRow(1, $viewer->id, '2026-01-02 09:00:00');

    $result = (new TicketViewsStage)->run(ticketViewsStageContext(limit: 1));

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(1);
});

test('re-running the stage on the same dump is idempotent: second run only skips, no duplicate rows', function (): void {
    $viewer = User::factory()->create();
    insertTicketForViews(1);
    insertLegacyWatchLogRow(1, $viewer->id, '2026-01-01 09:00:00');
    insertLegacyWatchLogRow(1, $viewer->id, '2026-01-01 14:00:00');

    $stage = new TicketViewsStage;
    $first = $stage->run(ticketViewsStageContext());
    $second = $stage->run(ticketViewsStageContext());

    expect($first->created)->toBe(1)
        ->and($second->created)->toBe(0)
        ->and($second->skipped)->toBe(2)
        ->and(DB::table('ticket_views')->count())->toBe(1);

    $view = DB::table('ticket_views')->first();
    expect((int) $view->view_count)->toBe(2);
});

test('ticket_logs and ticket_views read the same story_logs input with zero overlap', function (): void {
    $viewer = User::factory()->create();
    insertTicketForViews(1);
    insertLegacyWatchLogRow(1, $viewer->id, '2026-01-01 09:00:00');
    insertLegacyWatchLogRow(1, $viewer->id, '2026-01-02 09:00:00');

    DB::connection('legacy')->table('story_logs')->insert([
        'story_id' => 1,
        'user_id' => $viewer->id,
        'viewed_at' => '2026-01-03 09:00:00',
        'changes' => json_encode(['status' => 'assigned']),
        'created_at' => '2026-01-03 09:00:00',
        'updated_at' => '2026-01-03 09:00:00',
    ]);

    $logsResult = (new TicketLogsStage)->run(ticketViewsStageContext());
    $viewsResult = (new TicketViewsStage)->run(ticketViewsStageContext());

    expect($logsResult->read)->toBe(3)
        ->and($viewsResult->read)->toBe(3)
        ->and(DB::table('ticket_logs')->count())->toBe(1)
        ->and(DB::table('ticket_views')->count())->toBe(2)
        ->and($logsResult->created)->toBe(1)
        ->and($viewsResult->created)->toBe(2);
});
