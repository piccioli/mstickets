<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketMessageChannel;
use App\Domain\Ticketing\Enums\TicketMessageVisibility;
use App\Import\Enums\ImportRunStatus;
use App\Import\Models\ImportMapping;
use App\Import\Models\ImportRun;
use App\Import\Stages\ImportContext;
use App\Import\Stages\TicketMessagesStage;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Import\Fixtures\InteractsWithLegacyDatabase;

uses(RefreshDatabase::class, InteractsWithLegacyDatabase::class);

function ticketMessagesStageContext(bool $dryRun = false, ?int $limit = null, bool $anonymize = false): ImportContext
{
    $importRun = ImportRun::create([
        'started_at' => now(),
        'dump_label' => 'test-dump',
        'stages' => [],
        'status' => ImportRunStatus::Running,
        'is_dry_run' => $dryRun,
    ]);

    return new ImportContext(importRun: $importRun, dryRun: $dryRun, limit: $limit, anonymize: $anonymize);
}

beforeEach(function (): void {
    $this->useSqliteLegacyConnection();

    Schema::connection('legacy')->create('stories', function (Blueprint $table): void {
        $table->id();
        $table->text('customer_request')->nullable();
    });
});

function insertLegacyStoryForMessages(int $id, ?string $customerRequest): void
{
    DB::connection('legacy')->table('stories')->insert([
        'id' => $id,
        'customer_request' => $customerRequest,
    ]);
}

function insertTicketForMessages(int $id, ?int $requesterId = null, ?string $createdAt = null, ?string $updatedAt = null): void
{
    DB::table('tickets')->insert([
        'id' => $id,
        'title' => "Ticket {$id}",
        'status' => 'new',
        'status_changed_at' => now(),
        'type' => 'helpdesk',
        'priority' => 'low',
        'requester_id' => $requesterId,
        'worked_minutes' => 0,
        'created_at' => $createdAt ?? now(),
        'updated_at' => $updatedAt ?? now(),
    ]);
}

/**
 * Estratto reale (anonimizzato solo negli id) dal dump v1
 * (`v1dumps/production_dump_20260726_101158.sql`, story id 1641): due autori si
 * rispondono a vicenda 5 volte, ognuna prepesa come blocco "ha risposto il:", con il
 * contenuto originale del ticket in coda (il più vecchio).
 */
function realMultiMessageCustomerRequest(): string
{
    return "Riccardo Bernasconi ha risposto il: 21-01-2026 11:54\n <div style='background-color: #f8f9fa; border-left: 4px solid #6c757d; padding: 10px 20px;'> <p><p>Ciao Marco, allora si procede su due fronti.</p> </p> </div><div style='height: 2px; background-color: #e2e8f0; margin: 20px 0;'></div>OTCO/SO CCEC ha risposto il: 20-01-2026 13:58\n <div style='background-color: #fff7e6; border-left: 4px solid #ffa940; padding: 10px 20px;'> <p><p>sulla piattaforma è stata di fatto azzerata la Commissione</p> </p> </div><div style='height: 2px; background-color: #e2e8f0; margin: 20px 0;'></div><p>aggiornare l'OTCO CCEC in piattaforma </p>";
}

/**
 * Estratto reale dal dump v1 (story id 3642): citazione Gmail annidata in
 * `<blockquote>`, mai riconosciuta come blocco "ha risposto il:" — resta un unico
 * messaggio con l'HTML integrale (fallback).
 */
function realUnparseableGmailQuoteCustomerRequest(): string
{
    return '<p>Il giorno gio 4 giu 2026 alle ore 10:18 Editoria CAI &lt;editoria@cai.it&gt; ha scritto:</p><blockquote><p>Ciao Ivo, ti rispondo a nome di tutti.</p></blockquote><p>Grazie mille!</p>';
}

test('a real multi-reply customer_request is decomposed into chronological messages', function (): void {
    $requester = User::factory()->create(['name' => 'Marco Rossi']);
    $riccardo = User::factory()->create(['name' => 'Riccardo Bernasconi']);
    insertTicketForMessages(1641, requesterId: $requester->id, createdAt: '2026-01-10 09:00:00', updatedAt: '2026-01-21 12:00:00');
    insertLegacyStoryForMessages(1641, realMultiMessageCustomerRequest());

    $result = (new TicketMessagesStage)->run(ticketMessagesStageContext());

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(3)
        ->and($result->skipped)->toBe(0);

    $messages = DB::table('ticket_messages')->where('ticket_id', 1641)->orderBy('posted_at')->get();

    expect($messages)->toHaveCount(3);

    // Il contenuto originale è il più vecchio, ereditato dal requester del ticket.
    expect($messages[0]->author_id)->toBe($requester->id)
        ->and($messages[0]->body_text)->toContain("aggiornare l'OTCO CCEC in piattaforma")
        ->and($messages[0]->posted_at)->toBe('2026-01-10 09:00:00');

    // Il secondo messaggio (autore non riconosciuto, "OTCO/SO CCEC") non risolve un utente.
    expect($messages[1]->author_id)->toBeNull()
        ->and($messages[1]->body_text)->toContain('azzerata la Commissione')
        ->and($messages[1]->posted_at)->toBe('2026-01-20 13:58:00');

    // Il terzo (più recente) risolve l'autore per nome.
    expect($messages[2]->author_id)->toBe($riccardo->id)
        ->and($messages[2]->body_text)->toContain('Ciao Marco')
        ->and($messages[2]->posted_at)->toBe('2026-01-21 11:54:00');

    foreach ($messages as $message) {
        expect((bool) $message->is_legacy_import)->toBeTrue()
            ->and($message->visibility)->toBe(TicketMessageVisibility::Public->value)
            ->and($message->channel)->toBe(TicketMessageChannel::System->value)
            ->and($message->ulid)->not->toBeNull();
    }
});

test('an unparseable real conversation falls back to a single sanitized message', function (): void {
    insertTicketForMessages(3642, createdAt: '2026-06-01 08:00:00');
    insertLegacyStoryForMessages(3642, realUnparseableGmailQuoteCustomerRequest());

    $result = (new TicketMessagesStage)->run(ticketMessagesStageContext());

    expect($result->created)->toBe(1)
        ->and($result->warnings)->toContain('1 ticket con conversazione non scomponibile: importati come unico messaggio con l\'HTML integrale sanitizzato (fallback).');

    $message = DB::table('ticket_messages')->where('ticket_id', 3642)->first();

    expect($message->posted_at)->toBe('2026-06-01 08:00:00')
        ->and($message->body_text)->toContain('Ciao Ivo, ti rispondo a nome di tutti.')
        ->and($message->channel)->toBe(TicketMessageChannel::Email->value)
        ->and((bool) $message->is_legacy_import)->toBeTrue();
});

test('a ticket without any customer_request produces no message and is counted, not treated as fallback', function (): void {
    insertTicketForMessages(1);
    insertLegacyStoryForMessages(1, null);
    insertTicketForMessages(2);
    insertLegacyStoryForMessages(2, '   ');

    $result = (new TicketMessagesStage)->run(ticketMessagesStageContext());

    expect($result->read)->toBe(2)
        ->and($result->created)->toBe(0)
        ->and($result->skipped)->toBe(2)
        ->and($result->warnings)->toContain('2 ticket senza alcuna conversazione (customer_request vuoto).')
        ->and(DB::table('ticket_messages')->count())->toBe(0);
});

test('an XSS attempt in the body is neutralized by TicketMessageSanitizer', function (): void {
    insertTicketForMessages(1);
    insertLegacyStoryForMessages(1, '<p>Testo normale</p><script>alert(document.cookie)</script><img src=x onerror="alert(1)">');

    $result = (new TicketMessagesStage)->run(ticketMessagesStageContext());

    expect($result->created)->toBe(1);

    $message = DB::table('ticket_messages')->where('ticket_id', 1)->first();

    expect($message->body_html)->not->toContain('<script')
        ->and($message->body_html)->not->toContain('onerror')
        ->and($message->body_html)->not->toContain('alert(')
        ->and($message->body_text)->toContain('Testo normale');
});

test('a conversation referencing a non-existent v2 ticket is discarded and reported, not crashed', function (): void {
    insertLegacyStoryForMessages(999, '<p>Contenuto orfano</p>');

    $result = (new TicketMessagesStage)->run(ticketMessagesStageContext());

    expect($result->skipped)->toBe(1)
        ->and($result->warnings)->toContain('1 conversazioni scartate: ticket v1 inesistente in v2.')
        ->and(DB::table('ticket_messages')->count())->toBe(0);
});

test('dry-run does not write any ticket_messages or import_mappings row', function (): void {
    insertTicketForMessages(1);
    insertLegacyStoryForMessages(1, '<p>Contenuto</p>');

    $result = (new TicketMessagesStage)->run(ticketMessagesStageContext(dryRun: true));

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(0)
        ->and(DB::table('ticket_messages')->count())->toBe(0)
        ->and(ImportMapping::query()->count())->toBe(0);
});

test('--limit caps the number of source rows read', function (): void {
    insertTicketForMessages(1);
    insertLegacyStoryForMessages(1, '<p>Uno</p>');
    insertTicketForMessages(2);
    insertLegacyStoryForMessages(2, '<p>Due</p>');

    $result = (new TicketMessagesStage)->run(ticketMessagesStageContext(limit: 1));

    expect($result->read)->toBe(1)
        ->and($result->created)->toBe(1);
});

test('re-running the stage on the same dump is idempotent via import_mappings: second run only skips', function (): void {
    insertTicketForMessages(1641, createdAt: '2026-01-10 09:00:00', updatedAt: '2026-01-21 12:00:00');
    insertLegacyStoryForMessages(1641, realMultiMessageCustomerRequest());

    $stage = new TicketMessagesStage;
    $first = $stage->run(ticketMessagesStageContext());
    $second = $stage->run(ticketMessagesStageContext());

    expect($first->created)->toBe(3)
        ->and($second->created)->toBe(0)
        ->and($second->skipped)->toBe(3)
        ->and(DB::table('ticket_messages')->count())->toBe(3)
        ->and(ImportMapping::query()->where('target_table', 'ticket_messages')->count())->toBe(3);
});

test('a message with no resolvable timestamp is distributed monotonically between created_at and updated_at', function (): void {
    insertTicketForMessages(1, createdAt: '2026-01-01 00:00:00', updatedAt: '2026-01-11 00:00:00');
    $malformed = "Autore ha risposto il: 31-04-2026 10:00\n <div style='background-color: #f8f9fa; border-left: 4px solid #6c757d; padding: 10px 20px;'> <p>Corpo</p> </div><div style='height: 2px; background-color: #e2e8f0; margin: 20px 0;'></div><p>Originale</p>";
    insertLegacyStoryForMessages(1, $malformed);

    $result = (new TicketMessagesStage)->run(ticketMessagesStageContext());

    expect($result->created)->toBe(2)
        ->and($result->warnings)->toContain('1 messaggi senza timestamp ricostruibile: posted_at distribuito monotonamente tra created_at/updated_at del ticket.');

    $messages = DB::table('ticket_messages')->where('ticket_id', 1)->orderBy('posted_at')->get();

    expect($messages[0]->posted_at)->toBe('2026-01-01 00:00:00')
        ->and($messages[1]->posted_at)->toBeGreaterThan('2026-01-01 00:00:00')
        ->and($messages[1]->posted_at)->toBeLessThanOrEqual('2026-01-11 00:00:00');
});

test('--anonymize replaces the message body with deterministic fake content, without changing channel or author resolution', function (): void {
    $requester = User::factory()->create(['name' => 'Marco Rossi']);
    $riccardo = User::factory()->create(['name' => 'Riccardo Bernasconi']);
    insertTicketForMessages(1641, requesterId: $requester->id, createdAt: '2026-01-10 09:00:00', updatedAt: '2026-01-21 12:00:00');
    insertLegacyStoryForMessages(1641, realMultiMessageCustomerRequest());

    $result = (new TicketMessagesStage)->run(ticketMessagesStageContext(anonymize: true));

    expect($result->created)->toBe(3);

    $messages = DB::table('ticket_messages')->where('ticket_id', 1641)->orderBy('posted_at')->get();

    // Le relazioni (autore risolto/non risolto per posizione) restano identiche al caso non anonimizzato.
    expect($messages[0]->author_id)->toBe($requester->id)
        ->and($messages[1]->author_id)->toBeNull()
        ->and($messages[2]->author_id)->toBe($riccardo->id)
        ->and($messages[0]->channel)->toBe(TicketMessageChannel::System->value);

    foreach ($messages as $message) {
        expect($message->body_text)->not->toContain('Ciao Marco')
            ->and($message->body_text)->not->toContain('Bernasconi')
            ->and($message->body_text)->not->toContain('Commissione')
            ->and($message->body_text)->not->toBe('');
    }
});

test('--anonymize does not break idempotency: the source key is computed from the original body, not the fake one', function (): void {
    insertTicketForMessages(1641, createdAt: '2026-01-10 09:00:00', updatedAt: '2026-01-21 12:00:00');
    insertLegacyStoryForMessages(1641, realMultiMessageCustomerRequest());

    $stage = new TicketMessagesStage;
    $first = $stage->run(ticketMessagesStageContext(anonymize: true));
    $second = $stage->run(ticketMessagesStageContext(anonymize: true));

    expect($first->created)->toBe(3)
        ->and($second->created)->toBe(0)
        ->and($second->skipped)->toBe(3)
        ->and(DB::table('ticket_messages')->count())->toBe(3);
});
