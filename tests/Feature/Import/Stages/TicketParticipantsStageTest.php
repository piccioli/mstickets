<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Import\Enums\ImportRunStatus;
use App\Import\Models\ImportRun;
use App\Import\Stages\ImportContext;
use App\Import\Stages\TicketParticipantsStage;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Import\Fixtures\InteractsWithLegacyDatabase;

uses(RefreshDatabase::class, InteractsWithLegacyDatabase::class);

function ticketParticipantsStageContext(bool $dryRun = false, ?int $limit = null): ImportContext
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

    Schema::connection('legacy')->create('story_participants', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('story_id');
        $table->unsignedBigInteger('user_id');
        $table->timestamp('created_at')->nullable();
        $table->timestamp('updated_at')->nullable();
    });
});

function insertLegacyStoryParticipant(int $storyId, int $userId): void
{
    DB::connection('legacy')->table('story_participants')->insert([
        'story_id' => $storyId,
        'user_id' => $userId,
        'created_at' => '2026-01-01 10:00:00',
        'updated_at' => '2026-01-01 10:00:00',
    ]);
}

function createV2TicketForParticipants(int $id): void
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

test('imports the v1 ticket<->participant pivot into v2', function (): void {
    createV2TicketForParticipants(1);
    User::factory()->create(['id' => 1]);
    insertLegacyStoryParticipant(1, 1);

    $result = (new TicketParticipantsStage)->run(ticketParticipantsStageContext());

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(1)
        ->and($result->skipped)->toBe(0)
        ->and($result->warnings)->toHaveCount(1)
        ->and($result->warnings[0])->toContain('1 partecipazioni esplicite lette dal v1');

    expect(DB::table('ticket_participants')->where('ticket_id', 1)->where('user_id', 1)->exists())->toBeTrue();
});

test('dry-run does not write any pivot row', function (): void {
    createV2TicketForParticipants(1);
    User::factory()->create(['id' => 1]);
    insertLegacyStoryParticipant(1, 1);

    $result = (new TicketParticipantsStage)->run(ticketParticipantsStageContext(dryRun: true));

    expect($result->created)->toBe(0)
        ->and(DB::table('ticket_participants')->count())->toBe(0);
});

test('re-running the stage on the same dump is idempotent: second run only skips', function (): void {
    createV2TicketForParticipants(1);
    User::factory()->create(['id' => 1]);
    User::factory()->create(['id' => 2]);
    insertLegacyStoryParticipant(1, 1);
    insertLegacyStoryParticipant(1, 2);

    $stage = new TicketParticipantsStage;
    $first = $stage->run(ticketParticipantsStageContext());
    $second = $stage->run(ticketParticipantsStageContext());

    expect($first->created)->toBe(2)
        ->and($second->created)->toBe(0)
        ->and($second->skipped)->toBe(2)
        ->and(DB::table('ticket_participants')->count())->toBe(2);
});

test('a participation referencing a non-existent v2 ticket is reported, not crashed', function (): void {
    User::factory()->create(['id' => 1]);
    insertLegacyStoryParticipant(999, 1);

    $result = (new TicketParticipantsStage)->run(ticketParticipantsStageContext());

    expect($result->created)->toBe(0)
        ->and($result->skipped)->toBe(1)
        ->and($result->warnings[0])->toContain('ticket inesistente')
        ->and(DB::table('ticket_participants')->count())->toBe(0);
});

test('a participation referencing a non-existent v2 user is reported, not crashed', function (): void {
    createV2TicketForParticipants(1);
    insertLegacyStoryParticipant(1, 999);

    $result = (new TicketParticipantsStage)->run(ticketParticipantsStageContext());

    expect($result->created)->toBe(0)
        ->and($result->skipped)->toBe(1)
        ->and($result->warnings[0])->toContain('utente inesistente')
        ->and(DB::table('ticket_participants')->count())->toBe(0);
});

test('--limit caps the number of v1 rows read', function (): void {
    createV2TicketForParticipants(1);
    User::factory()->create(['id' => 1]);
    User::factory()->create(['id' => 2]);
    insertLegacyStoryParticipant(1, 1);
    insertLegacyStoryParticipant(1, 2);

    $result = (new TicketParticipantsStage)->run(ticketParticipantsStageContext(limit: 1));

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(1);
});

test('reads zero rows produces no informational warning', function (): void {
    $result = (new TicketParticipantsStage)->run(ticketParticipantsStageContext());

    expect($result->read)->toBe(0)
        ->and($result->warnings)->toBe([]);
});
