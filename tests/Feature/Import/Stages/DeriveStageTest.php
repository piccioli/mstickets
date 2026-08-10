<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Import\Enums\ImportRunStatus;
use App\Import\Models\ImportRun;
use App\Import\Stages\DeriveStage;
use App\Import\Stages\ImportContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function deriveStageContext(bool $dryRun = false, ?int $limit = null): ImportContext
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

function insertTicketForDerive(int $id, array $attributes = []): void
{
    DB::table('tickets')->insert(array_merge([
        'id' => $id,
        'title' => "Ticket {$id}",
        'status' => 'new',
        'status_changed_at' => now(),
        'type' => 'helpdesk',
        'priority' => 'low',
        'worked_minutes' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ], $attributes));
}

function insertTicketLogForDerive(int $ticketId, array $attributes = []): void
{
    DB::table('ticket_logs')->insert(array_merge([
        'ticket_id' => $ticketId,
        'user_id' => null,
        'event' => 'status_changed',
        'from_status' => null,
        'to_status' => null,
        'changes' => null,
        'is_system' => false,
        'occurred_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ], $attributes));
}

function insertFundraisingOpportunityForDerive(int $id, int $userId): void
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

function insertFundraisingScoreForDerive(int $opportunityId, string $criterionKey, int $score): void
{
    DB::table('fundraising_evaluation_scores')->insert([
        'fundraising_opportunity_id' => $opportunityId,
        'criterion_key' => $criterionKey,
        'score' => $score,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

test('backfills released_at from the ticket_logs status_changed transition, when missing', function (): void {
    insertTicketForDerive(1, ['status' => 'released', 'released_at' => null]);
    insertTicketLogForDerive(1, ['to_status' => 'progress', 'occurred_at' => '2026-01-05 09:00:00']);
    insertTicketLogForDerive(1, ['from_status' => 'progress', 'to_status' => 'released', 'occurred_at' => '2026-01-05 15:00:00']);

    $result = (new DeriveStage)->run(deriveStageContext());

    expect($result->updated)->toBeGreaterThanOrEqual(1);

    $ticket = DB::table('tickets')->where('id', 1)->first();
    expect($ticket->released_at)->not->toBeNull()
        ->and(Carbon::parse($ticket->released_at)->equalTo(Carbon::parse('2026-01-05 15:00:00')))->toBeTrue();
});

test('a released_at already present is never overwritten', function (): void {
    insertTicketForDerive(1, ['status' => 'released', 'released_at' => '2026-02-01 08:00:00']);
    insertTicketLogForDerive(1, ['from_status' => 'progress', 'to_status' => 'released', 'occurred_at' => '2026-01-05 15:00:00']);

    (new DeriveStage)->run(deriveStageContext());

    $ticket = DB::table('tickets')->where('id', 1)->first();
    expect(Carbon::parse($ticket->released_at)->equalTo(Carbon::parse('2026-02-01 08:00:00')))->toBeTrue();
});

test('a released ticket without a matching status_changed log keeps released_at null and is reported', function (): void {
    insertTicketForDerive(1, ['status' => 'released', 'released_at' => null]);

    $result = (new DeriveStage)->run(deriveStageContext());

    expect(DB::table('tickets')->where('id', 1)->value('released_at'))->toBeNull()
        ->and($result->warnings)->toContain('1 ticket in stato released/done senza un log di transizione corrispondente: timestamp non ricostruibile, rimasto null.');
});

test('recomputes worked_minutes and ticket_work_logs from a progress interval in ticket_logs, reusing RecalculateWorkedTime', function (): void {
    $developer = User::factory()->create();
    insertTicketForDerive(1, ['worked_minutes' => 0]);
    insertTicketLogForDerive(1, ['user_id' => $developer->id, 'to_status' => 'progress', 'occurred_at' => '2026-01-05 09:00:00']);
    insertTicketLogForDerive(1, ['user_id' => $developer->id, 'from_status' => 'progress', 'to_status' => 'testing', 'occurred_at' => '2026-01-05 11:00:00']);

    (new DeriveStage)->run(deriveStageContext());

    $ticket = DB::table('tickets')->where('id', 1)->first();
    expect((int) $ticket->worked_minutes)->toBe(120)
        ->and(DB::table('ticket_work_logs')->where('ticket_id', 1)->sum('minutes'))->toBe(120);
});

test('recalculates fundraising evaluation totals from a known set of scores', function (): void {
    $user = User::factory()->create();
    insertFundraisingOpportunityForDerive(1, $user->id);
    insertFundraisingScoreForDerive(1, 'impatto_sociale', 5);
    insertFundraisingScoreForDerive(1, 'rischi_finanziari', -3);
    insertFundraisingScoreForDerive(1, 'sostenibilita', 2);

    $result = (new DeriveStage)->run(deriveStageContext());

    $opportunity = DB::table('fundraising_opportunities')->where('id', 1)->first();

    expect((int) $opportunity->evaluation_positive_total)->toBe(7)
        ->and((int) $opportunity->evaluation_negative_total)->toBe(-3)
        ->and((int) $opportunity->evaluation_total)->toBe(4)
        ->and($result->warnings)->toContain('1 opportunità fundraising con totali di valutazione ricalcolati da fundraising_evaluation_scores.');
});

test('an opportunity with no evaluation score rows is left untouched (never evaluated, not zeroed)', function (): void {
    $user = User::factory()->create();
    insertFundraisingOpportunityForDerive(1, $user->id);

    (new DeriveStage)->run(deriveStageContext());

    $opportunity = DB::table('fundraising_opportunities')->where('id', 1)->first();

    expect($opportunity->evaluation_positive_total)->toBeNull()
        ->and($opportunity->evaluation_negative_total)->toBeNull()
        ->and($opportunity->evaluation_total)->toBeNull();
});

test('regenerates unique final slugs for tags and documentation_pages, numeric suffix on duplicates', function (): void {
    DB::table('tags')->insert([
        ['id' => 1, 'name' => 'Foo', 'slug' => 'stale-1', 'created_at' => now(), 'updated_at' => now()],
        ['id' => 2, 'name' => 'Foo', 'slug' => 'stale-2', 'created_at' => now(), 'updated_at' => now()],
    ]);
    DB::table('documentation_pages')->insert([
        ['id' => 1, 'title' => 'Guida', 'slug' => 'stale-doc', 'body' => 'corpo', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $result = (new DeriveStage)->run(deriveStageContext());

    expect(DB::table('tags')->where('id', 1)->value('slug'))->toBe('foo')
        ->and(DB::table('tags')->where('id', 2)->value('slug'))->toBe('foo-2')
        ->and(DB::table('documentation_pages')->where('id', 1)->value('slug'))->toBe('guida')
        ->and($result->warnings)->toContain('2 slug definitivi rigenerati su tags.')
        ->and($result->warnings)->toContain('1 slug definitivi rigenerati su documentation_pages.');
});

test('generates one email_thread per ticket with an imported conversation', function (): void {
    $author = User::factory()->create(['email' => 'author@example.org']);
    insertTicketForDerive(1, ['title' => 'Problema di accesso']);
    DB::table('ticket_messages')->insert([
        [
            'ulid' => (string) Str::ulid(),
            'ticket_id' => 1,
            'author_id' => $author->id,
            'author_email' => null,
            'channel' => 'email',
            'visibility' => 'public',
            'body_html' => '<p>Ciao</p>',
            'is_legacy_import' => true,
            'posted_at' => '2026-01-05 09:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'ulid' => (string) Str::ulid(),
            'ticket_id' => 1,
            'author_id' => null,
            'author_email' => 'cliente@example.org',
            'channel' => 'email',
            'visibility' => 'public',
            'body_html' => '<p>Risposta</p>',
            'is_legacy_import' => true,
            'posted_at' => '2026-01-06 10:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $result = (new DeriveStage)->run(deriveStageContext());

    $thread = DB::table('email_threads')->where('ticket_id', 1)->first();

    expect($thread)->not->toBeNull()
        ->and($thread->subject_normalized)->toBe('problema di accesso')
        ->and(json_decode((string) $thread->participants, true))->toEqualCanonicalizing(['author@example.org', 'cliente@example.org'])
        ->and(Carbon::parse($thread->last_message_at)->equalTo(Carbon::parse('2026-01-06 10:00:00')))->toBeTrue()
        ->and($result->created)->toBeGreaterThanOrEqual(1);
});

test('dry-run does not write any row anywhere', function (): void {
    $user = User::factory()->create();
    insertTicketForDerive(1, ['status' => 'released', 'released_at' => null, 'worked_minutes' => 0]);
    insertTicketLogForDerive(1, ['to_status' => 'progress', 'occurred_at' => '2026-01-05 09:00:00']);
    insertTicketLogForDerive(1, ['from_status' => 'progress', 'to_status' => 'released', 'occurred_at' => '2026-01-05 11:00:00']);
    insertFundraisingOpportunityForDerive(2, $user->id);
    insertFundraisingScoreForDerive(2, 'impatto_sociale', 5);
    DB::table('tags')->insert(['id' => 1, 'name' => 'Foo', 'slug' => 'stale', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('ticket_messages')->insert([
        'ulid' => (string) Str::ulid(),
        'ticket_id' => 1,
        'author_id' => null,
        'author_email' => null,
        'channel' => 'system',
        'visibility' => 'public',
        'body_html' => '<p>Ciao</p>',
        'is_legacy_import' => true,
        'posted_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $result = (new DeriveStage)->run(deriveStageContext(dryRun: true));

    expect($result->created)->toBe(0)
        ->and($result->updated)->toBe(0)
        ->and($result->skipped)->toBe(0)
        ->and(DB::table('tickets')->where('id', 1)->value('released_at'))->toBeNull()
        ->and((int) DB::table('tickets')->where('id', 1)->value('worked_minutes'))->toBe(0)
        ->and(DB::table('fundraising_opportunities')->where('id', 2)->value('evaluation_total'))->toBeNull()
        ->and(DB::table('tags')->where('id', 1)->value('slug'))->toBe('stale')
        ->and(DB::table('email_threads')->count())->toBe(0);
});

test('--limit caps only the ticket worked-time/timestamp recompute, not the other derivations', function (): void {
    insertTicketForDerive(1, ['worked_minutes' => 0]);
    insertTicketForDerive(2, ['worked_minutes' => 0]);
    $user = User::factory()->create();
    insertFundraisingOpportunityForDerive(1, $user->id);
    insertFundraisingScoreForDerive(1, 'impatto_sociale', 5);

    $result = (new DeriveStage)->run(deriveStageContext(limit: 1));

    expect($result->warnings)->toContain('1 opportunità fundraising con totali di valutazione ricalcolati da fundraising_evaluation_scores.');
});

test('re-running derive on the same state is idempotent: second run only skips', function (): void {
    $user = User::factory()->create();
    insertTicketForDerive(1, ['status' => 'released', 'released_at' => null, 'worked_minutes' => 0]);
    insertTicketLogForDerive(1, ['to_status' => 'progress', 'occurred_at' => '2026-01-05 09:00:00']);
    insertTicketLogForDerive(1, ['from_status' => 'progress', 'to_status' => 'released', 'occurred_at' => '2026-01-05 11:00:00']);
    insertFundraisingOpportunityForDerive(2, $user->id);
    insertFundraisingScoreForDerive(2, 'impatto_sociale', 5);
    DB::table('tags')->insert(['id' => 1, 'name' => 'Foo', 'slug' => 'stale', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('ticket_messages')->insert([
        'ulid' => (string) Str::ulid(),
        'ticket_id' => 1,
        'author_id' => null,
        'author_email' => 'cliente@example.org',
        'channel' => 'email',
        'visibility' => 'public',
        'body_html' => '<p>Ciao</p>',
        'is_legacy_import' => true,
        'posted_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $stage = new DeriveStage;
    $first = $stage->run(deriveStageContext());
    $second = $stage->run(deriveStageContext());

    expect($second->created)->toBe(0)
        ->and($second->updated)->toBe(0)
        ->and($second->skipped)->toBeGreaterThan(0)
        ->and(DB::table('email_threads')->count())->toBe(1)
        ->and($first->created)->toBeGreaterThanOrEqual(1);
});

test('declares its dependencies on every stage whose output it reads', function (): void {
    $stage = new DeriveStage;

    expect($stage->name())->toBe('derive')
        ->and($stage->dependencies())->toBe([
            'tickets', 'ticket_logs', 'tags', 'documentation', 'fundraising_opportunities', 'fundraising_scores', 'ticket_messages',
        ]);
});
