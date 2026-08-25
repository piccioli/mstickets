<?php

declare(strict_types=1);

use App\Domain\Mail\Actions\ResolveEmailThread;
use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Enums\ThreadMatchLevel;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Mail\Models\EmailThread;
use App\Domain\Ticketing\Enums\TicketMessageChannel;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function makeTicketForThreadResolution(array $attributes = []): Ticket
{
    return Ticket::create(array_merge(['title' => 'Ticket di test', 'status_changed_at' => now()], $attributes));
}

function makeThreadMessage(Ticket $ticket, array $attributes = []): TicketMessage
{
    return TicketMessage::create(array_merge([
        'ticket_id' => $ticket->id,
        'channel' => TicketMessageChannel::Email,
        'posted_at' => now(),
    ], $attributes));
}

function makeInboundEmail(array $attributes = []): EmailMessage
{
    return EmailMessage::create(array_merge([
        'direction' => EmailDirection::Inbound,
        'status' => EmailStatus::Classified,
        'from_email' => 'cliente@example.test',
        'subject' => 'placeholder',
        'received_at' => now(),
    ], $attributes));
}

test('livello 1 (VERP): un token ticket+ulid valido nel To risolve il ticket_message e il suo ticket', function (): void {
    $ticket = makeTicketForThreadResolution();
    $notification = makeThreadMessage($ticket);

    $email = makeInboundEmail(['to' => ["ticket+{$notification->ulid}@support.example.test"]]);

    $result = ResolveEmailThread::run($email);

    expect($result->isMatch())->toBeTrue()
        ->and($result->ticketId)->toBe($ticket->id)
        ->and($result->matchLevel)->toBe(ThreadMatchLevel::Verp)
        ->and($result->isHeuristic())->toBeFalse();
});

test('livello 1 (VERP): il match è case-insensitive sull\'ulid', function (): void {
    $ticket = makeTicketForThreadResolution();
    $notification = makeThreadMessage($ticket);

    $email = makeInboundEmail(['to' => ['ticket+'.mb_strtolower((string) $notification->ulid).'@support.example.test']]);

    $result = ResolveEmailThread::run($email);

    expect($result->ticketId)->toBe($ticket->id)
        ->and($result->matchLevel)->toBe(ThreadMatchLevel::Verp);
});

test('livello 1 (VERP): un token che referenzia l\'ulid di una email_messages outbound (nessun ticket_message, es. E2) risolve comunque il ticket', function (): void {
    $ticket = makeTicketForThreadResolution();
    $outboundNotification = EmailMessage::create([
        'direction' => EmailDirection::Outbound,
        'status' => EmailStatus::Queued,
        'from_email' => 'noreply@example.test',
        'ticket_id' => $ticket->id,
    ]);

    $email = makeInboundEmail(['to' => ["ticket+{$outboundNotification->ulid}@support.example.test"]]);

    $result = ResolveEmailThread::run($email);

    expect($result->isMatch())->toBeTrue()
        ->and($result->ticketId)->toBe($ticket->id)
        ->and($result->matchLevel)->toBe(ThreadMatchLevel::Verp);
});

test('livello 1 (VERP): un ulid sconosciuto non produce match e si passa al livello successivo', function (): void {
    $email = makeInboundEmail(['to' => ['ticket+'.Str::upper((string) Str::ulid()).'@support.example.test']]);

    $result = ResolveEmailThread::run($email);

    expect($result->isMatch())->toBeFalse();
});

test('livello 2 (In-Reply-To): un In-Reply-To che referenzia un message_id esistente collegato a un ticket risolve quel ticket', function (): void {
    $ticket = makeTicketForThreadResolution();
    makeInboundEmail(['message_id' => 'notifica-1@example.test', 'ticket_id' => $ticket->id]);

    $email = makeInboundEmail(['in_reply_to' => 'notifica-1@example.test']);

    $result = ResolveEmailThread::run($email);

    expect($result->ticketId)->toBe($ticket->id)
        ->and($result->matchLevel)->toBe(ThreadMatchLevel::InReplyTo);
});

test('livello 2 (References): un token nella lista References (separata da spazi) risolve il ticket', function (): void {
    $ticket = makeTicketForThreadResolution();
    makeInboundEmail(['message_id' => 'notifica-2@example.test', 'ticket_id' => $ticket->id]);

    $email = makeInboundEmail(['references' => 'altro-id@example.test notifica-2@example.test']);

    $result = ResolveEmailThread::run($email);

    expect($result->ticketId)->toBe($ticket->id)
        ->and($result->matchLevel)->toBe(ThreadMatchLevel::InReplyTo);
});

test('livello 2: un In-Reply-To valido non viene mai scavalcato dall\'euristica (livello 4)', function (): void {
    $ticket = makeTicketForThreadResolution();
    $otherTicket = makeTicketForThreadResolution(['title' => 'Altro problema']);
    makeInboundEmail(['message_id' => 'notifica-3@example.test', 'ticket_id' => $ticket->id]);

    EmailThread::create([
        'ticket_id' => $otherTicket->id,
        'subject_normalized' => 'stesso oggetto',
        'participants' => ['cliente@example.test'],
        'last_message_at' => now(),
    ]);

    $email = makeInboundEmail([
        'in_reply_to' => 'notifica-3@example.test',
        'subject' => 'Stesso oggetto',
        'from_email' => 'cliente@example.test',
    ]);

    $result = ResolveEmailThread::run($email);

    expect($result->ticketId)->toBe($ticket->id)
        ->and($result->matchLevel)->toBe(ThreadMatchLevel::InReplyTo);
});

test('livello 3 (token subject): [#<id>] nel subject normalizzato risolve direttamente il ticket', function (): void {
    $ticket = makeTicketForThreadResolution();

    $email = makeInboundEmail(['subject' => "[#{$ticket->id}] Ticket di test"]);

    $result = ResolveEmailThread::run($email);

    expect($result->ticketId)->toBe($ticket->id)
        ->and($result->matchLevel)->toBe(ThreadMatchLevel::SubjectToken);
});

test('livello 3: un token subject valido non viene mai scavalcato dall\'euristica', function (): void {
    $ticket = makeTicketForThreadResolution();
    $otherTicket = makeTicketForThreadResolution(['title' => 'Altro ticket']);

    EmailThread::create([
        'ticket_id' => $otherTicket->id,
        'subject_normalized' => 'ticket di test',
        'participants' => ['cliente@example.test'],
        'last_message_at' => now(),
    ]);

    $email = makeInboundEmail([
        'subject' => "[#{$ticket->id}] Ticket di test",
        'from_email' => 'cliente@example.test',
    ]);

    $result = ResolveEmailThread::run($email);

    expect($result->ticketId)->toBe($ticket->id)
        ->and($result->matchLevel)->toBe(ThreadMatchLevel::SubjectToken);
});

test('livello 4 (euristica): stesso mittente + subject normalizzato identico + thread aperto di recente risolve il ticket, marcato esplicitamente come euristico', function (): void {
    $ticket = makeTicketForThreadResolution();

    EmailThread::create([
        'ticket_id' => $ticket->id,
        'subject_normalized' => 'problema di accesso',
        'participants' => ['cliente@example.test'],
        'last_message_at' => now()->subDays(5),
    ]);

    $email = makeInboundEmail([
        'subject' => 'Re: Problema di accesso',
        'from_email' => 'cliente@example.test',
    ]);

    $result = ResolveEmailThread::run($email);

    expect($result->ticketId)->toBe($ticket->id)
        ->and($result->matchLevel)->toBe(ThreadMatchLevel::Heuristic)
        ->and($result->isHeuristic())->toBeTrue();
});

test('livello 4: un mittente diverso da tutti i partecipanti del thread non produce match', function (): void {
    $ticket = makeTicketForThreadResolution();

    EmailThread::create([
        'ticket_id' => $ticket->id,
        'subject_normalized' => 'problema di accesso',
        'participants' => ['altro-cliente@example.test'],
        'last_message_at' => now(),
    ]);

    $email = makeInboundEmail([
        'subject' => 'Problema di accesso',
        'from_email' => 'cliente@example.test',
    ]);

    expect(ResolveEmailThread::run($email)->isMatch())->toBeFalse();
});

test('livello 4: un thread fuori dalla finestra configurata non produce match', function (): void {
    config(['mail_pipeline.threading.heuristic_window_days' => 30]);

    $ticket = makeTicketForThreadResolution();

    EmailThread::create([
        'ticket_id' => $ticket->id,
        'subject_normalized' => 'problema di accesso',
        'participants' => ['cliente@example.test'],
        'last_message_at' => now()->subDays(31),
    ]);

    $email = makeInboundEmail([
        'subject' => 'Problema di accesso',
        'from_email' => 'cliente@example.test',
    ]);

    expect(ResolveEmailThread::run($email)->isMatch())->toBeFalse();
});

test('nessun match su nessuno dei quattro livelli restituisce una risoluzione vuota (nuovo ticket)', function (): void {
    $email = makeInboundEmail(['subject' => 'Richiesta mai vista prima', 'from_email' => 'nuovo@example.test']);

    $result = ResolveEmailThread::run($email);

    expect($result->isMatch())->toBeFalse()
        ->and($result->ticketId)->toBeNull()
        ->and($result->matchLevel)->toBeNull();
});
