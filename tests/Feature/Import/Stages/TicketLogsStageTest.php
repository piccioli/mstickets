<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Import\Enums\ImportRunStatus;
use App\Import\Models\ImportMapping;
use App\Import\Models\ImportRun;
use App\Import\Stages\ImportContext;
use App\Import\Stages\TicketLogsStage;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Import\Fixtures\InteractsWithLegacyDatabase;

uses(RefreshDatabase::class, InteractsWithLegacyDatabase::class);

function ticketLogsStageContext(bool $dryRun = false, ?int $limit = null): ImportContext
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

function insertTicketForLogs(int $id): void
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

function insertLegacyStoryLogRow(int $storyId, ?int $userId, array $changes, string $viewedAt): void
{
    DB::connection('legacy')->table('story_logs')->insert([
        'story_id' => $storyId,
        'user_id' => $userId,
        'viewed_at' => $viewedAt,
        'changes' => json_encode($changes),
        'created_at' => $viewedAt,
        'updated_at' => $viewedAt,
    ]);
}

test('a status delta becomes a status_changed event with from_status derived from the previous log', function (): void {
    $author = User::factory()->create();
    insertTicketForLogs(1);
    insertLegacyStoryLogRow(1, $author->id, ['status' => 'assigned'], '2026-01-01 10:00:00');
    insertLegacyStoryLogRow(1, $author->id, ['status' => 'released'], '2026-01-02 10:00:00');

    $result = (new TicketLogsStage)->run(ticketLogsStageContext());

    expect($result->read)->toBe(2)
        ->and($result->created)->toBe(2)
        ->and($result->skipped)->toBe(0)
        ->and($result->warnings)->toBe([]);

    $logs = DB::table('ticket_logs')->orderBy('id')->get();

    expect($logs[0]->event)->toBe('status_changed')
        ->and($logs[0]->from_status)->toBeNull()
        ->and($logs[0]->to_status)->toBe('assigned')
        ->and($logs[1]->event)->toBe('status_changed')
        ->and($logs[1]->from_status)->toBe('assigned')
        ->and($logs[1]->to_status)->toBe('released');
});

test('a user_id-only delta becomes an assigned event', function (): void {
    $author = User::factory()->create();
    insertTicketForLogs(1);
    insertLegacyStoryLogRow(1, $author->id, ['updated_at' => '2026-01-01 10:00:00', 'user_id' => 6], '2026-01-01 10:00:00');

    $result = (new TicketLogsStage)->run(ticketLogsStageContext());

    expect($result->created)->toBe(1);

    $log = DB::table('ticket_logs')->first();

    expect($log->event)->toBe('assigned')
        ->and($log->from_status)->toBeNull()
        ->and($log->to_status)->toBeNull()
        ->and($log->changes)->toBeNull();
});

test('other keys become an updated event with the diff in changes, excluding bookkeeping keys', function (): void {
    $author = User::factory()->create();
    insertTicketForLogs(1);
    insertLegacyStoryLogRow(1, $author->id, ['updated_at' => '2026-01-01 10:00:00', 'creator_id' => '160'], '2026-01-01 10:00:00');

    $result = (new TicketLogsStage)->run(ticketLogsStageContext());

    expect($result->created)->toBe(1);

    $log = DB::table('ticket_logs')->first();

    expect($log->event)->toBe('updated')
        ->and(json_decode((string) $log->changes, true))->toBe(['creator_id' => '160']);
});

test('a description change never stores the body, only the "changed" marker', function (): void {
    $author = User::factory()->create();
    insertTicketForLogs(1);
    insertLegacyStoryLogRow(1, $author->id, [
        'updated_at' => '2026-01-01 10:00:00',
        'description' => '<script>alert(1)</script> corpo reale del messaggio',
    ], '2026-01-01 10:00:00');

    $result = (new TicketLogsStage)->run(ticketLogsStageContext());

    expect($result->created)->toBe(1);

    $log = DB::table('ticket_logs')->first();

    expect($log->event)->toBe('updated')
        ->and(json_decode((string) $log->changes, true))->toBe(['description' => 'changed'])
        ->and($log->changes)->not->toContain('alert(1)');
});

test('a log with only the watch key is excluded and reported, not imported as a ticket_log', function (): void {
    $author = User::factory()->create();
    insertTicketForLogs(1);
    insertLegacyStoryLogRow(1, $author->id, ['watch' => '2026-01-01 10:00:00'], '2026-01-01 10:00:00');

    $result = (new TicketLogsStage)->run(ticketLogsStageContext());

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(0)
        ->and($result->skipped)->toBe(1)
        ->and($result->warnings)->toHaveCount(1)
        ->and($result->warnings[0])->toContain('sola chiave "watch"');

    expect(DB::table('ticket_logs')->count())->toBe(0);
});

test('a log referencing a non-existent v2 ticket is discarded and reported, not crashed', function (): void {
    $author = User::factory()->create();
    insertLegacyStoryLogRow(999, $author->id, ['status' => 'assigned'], '2026-01-01 10:00:00');

    $result = (new TicketLogsStage)->run(ticketLogsStageContext());

    expect($result->skipped)->toBe(1)
        ->and($result->warnings)->toHaveCount(1)
        ->and($result->warnings[0])->toContain('ticket v1 inesistente in v2');

    expect(DB::table('ticket_logs')->count())->toBe(0);
});

test('a log without a resolvable author falls back to the system user', function (): void {
    insertTicketForLogs(1);
    insertLegacyStoryLogRow(1, null, ['status' => 'assigned'], '2026-01-01 10:00:00');

    $result = (new TicketLogsStage)->run(ticketLogsStageContext());

    expect($result->created)->toBe(1)
        ->and($result->warnings)->toHaveCount(1)
        ->and($result->warnings[0])->toContain('utente di sistema');

    $log = DB::table('ticket_logs')->first();
    $systemUser = User::system();

    expect($log->user_id)->toBe($systemUser->id)
        ->and((bool) $log->is_system)->toBeTrue();
});

test('dry-run does not write any ticket_logs or import_mappings row', function (): void {
    $author = User::factory()->create();
    insertTicketForLogs(1);
    insertLegacyStoryLogRow(1, $author->id, ['status' => 'assigned'], '2026-01-01 10:00:00');

    $result = (new TicketLogsStage)->run(ticketLogsStageContext(dryRun: true));

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(0)
        ->and(DB::table('ticket_logs')->count())->toBe(0)
        ->and(ImportMapping::query()->count())->toBe(0);
});

test('--limit caps the number of source rows read', function (): void {
    $author = User::factory()->create();
    insertTicketForLogs(1);
    insertLegacyStoryLogRow(1, $author->id, ['status' => 'assigned'], '2026-01-01 10:00:00');
    insertLegacyStoryLogRow(1, $author->id, ['status' => 'released'], '2026-01-02 10:00:00');

    $result = (new TicketLogsStage)->run(ticketLogsStageContext(limit: 1));

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(1);
});

test('re-running the stage on the same dump is idempotent via import_mappings: second run only skips', function (): void {
    $author = User::factory()->create();
    insertTicketForLogs(1);
    insertLegacyStoryLogRow(1, $author->id, ['status' => 'assigned'], '2026-01-01 10:00:00');
    insertLegacyStoryLogRow(1, $author->id, ['status' => 'released'], '2026-01-02 10:00:00');

    $stage = new TicketLogsStage;
    $first = $stage->run(ticketLogsStageContext());
    $second = $stage->run(ticketLogsStageContext());

    expect($first->created)->toBe(2)
        ->and($second->created)->toBe(0)
        ->and($second->skipped)->toBe(2)
        ->and(DB::table('ticket_logs')->count())->toBe(2)
        ->and(ImportMapping::query()->where('target_table', 'ticket_logs')->count())->toBe(2);
});
