<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Import\Enums\ImportRunStatus;
use App\Import\Models\ImportRun;
use App\Import\Stages\ImportContext;
use App\Import\Stages\TicketsStage;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Import\Fixtures\InteractsWithLegacyDatabase;

uses(RefreshDatabase::class, InteractsWithLegacyDatabase::class);

function ticketsStageContext(bool $dryRun = false, ?int $limit = null): ImportContext
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
        $table->timestamp('created_at')->nullable();
        $table->timestamp('updated_at')->nullable();
    });

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

function insertLegacyStory(array $attributes = []): void
{
    DB::connection('legacy')->table('stories')->insert(array_merge([
        'id' => 1,
        'name' => 'Ticket di prova',
        'description' => 'Descrizione',
        'status' => 'new',
        'type' => 'Help desk',
        'priority' => 1,
        'user_id' => null,
        'creator_id' => null,
        'tester_id' => null,
        'test_dev' => null,
        'test_prod' => null,
        'estimated_hours' => null,
        'fundraising_project_id' => null,
        'waiting_reason' => null,
        'problem_reason' => null,
        'released_at' => null,
        'done_at' => null,
        'created_at' => '2026-01-01 10:00:00',
        'updated_at' => '2026-01-01 10:00:00',
    ], $attributes));
}

function insertLegacyStoryLog(int $storyId, array $changes, string $viewedAt): void
{
    DB::connection('legacy')->table('story_logs')->insert([
        'story_id' => $storyId,
        'user_id' => 1,
        'viewed_at' => $viewedAt,
        'changes' => json_encode($changes),
        'created_at' => $viewedAt,
        'updated_at' => $viewedAt,
    ]);
}

test('imports a v1 story into v2 tickets with the id preserved and the main mapping applied', function (): void {
    $requester = User::factory()->create();
    $assignee = User::factory()->create();
    $tester = User::factory()->create();

    insertLegacyStory([
        'id' => 42,
        'name' => 'Errore login',
        'description' => 'Il cliente non riesce ad accedere',
        'status' => 'progress',
        'type' => 'Bug',
        'priority' => 3,
        'creator_id' => $requester->id,
        'user_id' => $assignee->id,
        'tester_id' => $tester->id,
        'test_dev' => 'https://dev.example.test',
        'test_prod' => 'https://example.test',
        'estimated_hours' => 4.5,
    ]);
    insertLegacyStoryLog(42, ['status' => 'progress'], '2026-01-01 11:00:00');

    $result = (new TicketsStage)->run(ticketsStageContext());

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(1)
        ->and($result->updated)->toBe(0)
        ->and($result->skipped)->toBe(0)
        ->and($result->warnings)->toBe([]);

    $ticket = DB::table('tickets')->where('id', 42)->first();

    expect($ticket)->not->toBeNull()
        ->and($ticket->title)->toBe('Errore login')
        ->and($ticket->description)->toBe('Il cliente non riesce ad accedere')
        ->and($ticket->status)->toBe('progress')
        ->and($ticket->type)->toBe('bug')
        ->and($ticket->priority)->toBe('high')
        ->and($ticket->requester_id)->toBe($requester->id)
        ->and($ticket->assignee_id)->toBe($assignee->id)
        ->and($ticket->tester_id)->toBe($tester->id)
        ->and($ticket->staging_url)->toBe('https://dev.example.test')
        ->and($ticket->production_url)->toBe('https://example.test')
        ->and((float) $ticket->estimated_hours)->toBe(4.5)
        ->and((int) $ticket->worked_minutes)->toBe(0)
        ->and($ticket->parent_id)->toBeNull();
});

test('type mapping is case-insensitive and tolerant of spaces', function (string $raw, string $expected): void {
    insertLegacyStory(['id' => 1, 'type' => $raw]);

    (new TicketsStage)->run(ticketsStageContext());

    expect(DB::table('tickets')->where('id', 1)->value('type'))->toBe($expected);
})->with([
    ['Bug', 'bug'],
    ['Feature', 'feature'],
    ['Help desk', 'helpdesk'],
    ['Helpdesk', 'helpdesk'],
    ['help desk', 'helpdesk'],
    ['Scrum', 'scrum'],
    ['scrum', 'scrum'],
]);

test('an unrecognized type defaults to helpdesk and is reported', function (): void {
    insertLegacyStory(['id' => 1, 'type' => 'Epic']);
    insertLegacyStoryLog(1, ['status' => 'new'], '2026-01-01 11:00:00');

    $result = (new TicketsStage)->run(ticketsStageContext());

    expect(DB::table('tickets')->where('id', 1)->value('type'))->toBe('helpdesk')
        ->and($result->warnings)->toHaveCount(1)
        ->and($result->warnings[0])->toContain('1 ticket con tipo v1 non riconosciuto')
        ->and($result->warnings[0])->toContain('"Epic"');
});

test('priority mapping: 1/2/3 map to low/medium/high', function (int $raw, string $expected): void {
    insertLegacyStory(['id' => 1, 'priority' => $raw]);

    (new TicketsStage)->run(ticketsStageContext());

    expect(DB::table('tickets')->where('id', 1)->value('priority'))->toBe($expected);
})->with([
    [1, 'low'],
    [2, 'medium'],
    [3, 'high'],
]);

test('an out-of-range priority defaults to low and is reported', function (): void {
    insertLegacyStory(['id' => 1, 'priority' => 9]);
    insertLegacyStoryLog(1, ['status' => 'new'], '2026-01-01 11:00:00');

    $result = (new TicketsStage)->run(ticketsStageContext());

    expect(DB::table('tickets')->where('id', 1)->value('priority'))->toBe('low')
        ->and($result->warnings)->toHaveCount(1)
        ->and($result->warnings[0])->toContain('1 ticket con priorità v1 fuori dai valori noti');
});

test('status_changed_at is derived from the most recent story_logs status change', function (): void {
    insertLegacyStory(['id' => 1, 'status' => 'progress', 'updated_at' => '2026-01-05 09:00:00']);
    insertLegacyStoryLog(1, ['status' => 'assigned'], '2026-01-01 10:00:00');
    insertLegacyStoryLog(1, ['status' => 'progress'], '2026-01-03 12:00:00');
    insertLegacyStoryLog(1, ['creator_id' => '41'], '2026-01-04 08:00:00');

    $result = (new TicketsStage)->run(ticketsStageContext());

    expect(DB::table('tickets')->where('id', 1)->value('status_changed_at'))->toBe('2026-01-03 12:00:00')
        ->and($result->warnings)->toBe([]);
});

test('status_changed_at falls back to stories.updated_at when no status log exists, and is reported', function (): void {
    insertLegacyStory(['id' => 1, 'status' => 'new', 'updated_at' => '2026-01-05 09:00:00']);
    insertLegacyStoryLog(1, ['creator_id' => '41'], '2026-01-04 08:00:00');

    $result = (new TicketsStage)->run(ticketsStageContext());

    expect(DB::table('tickets')->where('id', 1)->value('status_changed_at'))->toBe('2026-01-05 09:00:00')
        ->and($result->warnings)->toHaveCount(1)
        ->and($result->warnings[0])->toContain('1 ticket senza un cambio di stato ricostruibile');
});

test('previous_status for a waiting ticket walks back the logs to the first status different from waiting/problem', function (): void {
    insertLegacyStory(['id' => 1, 'status' => 'waiting']);
    insertLegacyStoryLog(1, ['status' => 'assigned'], '2026-01-01 10:00:00');
    insertLegacyStoryLog(1, ['status' => 'progress'], '2026-01-02 10:00:00');
    insertLegacyStoryLog(1, ['status' => 'problem'], '2026-01-03 10:00:00');
    insertLegacyStoryLog(1, ['status' => 'waiting'], '2026-01-04 10:00:00');

    $result = (new TicketsStage)->run(ticketsStageContext());

    expect(DB::table('tickets')->where('id', 1)->value('previous_status'))->toBe('progress')
        ->and($result->warnings)->toBe([]);
});

test('previous_status defaults to new when no differing status is reconstructible, and is reported', function (): void {
    insertLegacyStory(['id' => 1, 'status' => 'problem']);
    insertLegacyStoryLog(1, ['status' => 'waiting'], '2026-01-01 10:00:00');
    insertLegacyStoryLog(1, ['status' => 'problem'], '2026-01-02 10:00:00');

    $result = (new TicketsStage)->run(ticketsStageContext());

    expect(DB::table('tickets')->where('id', 1)->value('previous_status'))->toBe('new')
        ->and($result->warnings)->toHaveCount(1)
        ->and($result->warnings[0])->toContain('1 ticket in waiting/problem senza uno stato precedente');
});

test('previous_status stays null for a ticket not in waiting/problem', function (): void {
    insertLegacyStory(['id' => 1, 'status' => 'progress']);

    (new TicketsStage)->run(ticketsStageContext());

    expect(DB::table('tickets')->where('id', 1)->value('previous_status'))->toBeNull();
});

test('a user reference to a non-existent v2 user is nulled out and reported, not crashed', function (): void {
    insertLegacyStory(['id' => 1, 'creator_id' => 999, 'user_id' => 998, 'tester_id' => 997]);
    insertLegacyStoryLog(1, ['status' => 'new'], '2026-01-01 11:00:00');

    $result = (new TicketsStage)->run(ticketsStageContext());

    $ticket = DB::table('tickets')->where('id', 1)->first();

    expect($ticket->requester_id)->toBeNull()
        ->and($ticket->assignee_id)->toBeNull()
        ->and($ticket->tester_id)->toBeNull()
        ->and($result->warnings)->toHaveCount(1)
        ->and($result->warnings[0])->toContain('3 riferimenti utente');
});

test('dry-run does not write any ticket row', function (): void {
    insertLegacyStory(['id' => 1]);

    $result = (new TicketsStage)->run(ticketsStageContext(dryRun: true));

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(0)
        ->and(DB::table('tickets')->count())->toBe(0);
});

test('--limit caps the number of v1 rows read', function (): void {
    insertLegacyStory(['id' => 1]);
    insertLegacyStory(['id' => 2, 'name' => 'Secondo ticket']);

    $result = (new TicketsStage)->run(ticketsStageContext(limit: 1));

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(1);
});

test('re-running the stage on the same dump is idempotent: second run only skips', function (): void {
    insertLegacyStory(['id' => 1, 'status' => 'waiting']);
    insertLegacyStory(['id' => 2]);
    insertLegacyStoryLog(1, ['status' => 'progress'], '2026-01-01 10:00:00');
    insertLegacyStoryLog(1, ['status' => 'waiting'], '2026-01-02 10:00:00');

    $stage = new TicketsStage;
    $first = $stage->run(ticketsStageContext());
    $second = $stage->run(ticketsStageContext());

    expect($first->created)->toBe(2)
        ->and($second->created)->toBe(0)
        ->and($second->updated)->toBe(0)
        ->and($second->skipped)->toBe(2)
        ->and(DB::table('tickets')->count())->toBe(2);
});

test('a changed v1 row is applied as an update without resetting status_changed_at/previous_status/worked_minutes', function (): void {
    insertLegacyStory(['id' => 1, 'status' => 'waiting', 'name' => 'Titolo originale']);
    insertLegacyStoryLog(1, ['status' => 'progress'], '2026-01-01 10:00:00');
    insertLegacyStoryLog(1, ['status' => 'waiting'], '2026-01-02 10:00:00');

    (new TicketsStage)->run(ticketsStageContext());

    DB::table('tickets')->where('id', 1)->update([
        'status_changed_at' => '2026-06-01 00:00:00',
        'previous_status' => 'testing',
        'worked_minutes' => 120,
    ]);

    DB::connection('legacy')->table('stories')->where('id', 1)->update(['name' => 'Titolo aggiornato']);

    $result = (new TicketsStage)->run(ticketsStageContext());

    $ticket = DB::table('tickets')->where('id', 1)->first();

    expect($result->updated)->toBe(1)
        ->and($ticket->title)->toBe('Titolo aggiornato')
        ->and($ticket->status_changed_at)->toBe('2026-06-01 00:00:00')
        ->and($ticket->previous_status)->toBe('testing')
        ->and((int) $ticket->worked_minutes)->toBe(120);
});
