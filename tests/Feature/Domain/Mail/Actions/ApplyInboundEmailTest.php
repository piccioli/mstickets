<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Actions\ApplyInboundEmail;
use App\Domain\Mail\Enums\EmailAttachmentStatus;
use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Enums\SuppressionReason;
use App\Domain\Mail\Events\EmailQuarantined;
use App\Domain\Mail\Events\InboundEmailApplied;
use App\Domain\Mail\Models\EmailAttachment;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Mail\Models\EmailSuppression;
use App\Domain\Ticketing\Enums\TicketMessageChannel;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Enums\TicketType;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function makeClassifiedInboundEmail(array $attributes = []): EmailMessage
{
    return EmailMessage::create(array_merge([
        'direction' => EmailDirection::Inbound,
        'status' => EmailStatus::Classified,
        'from_email' => 'cliente@example.test',
        'subject' => 'Non riesco ad accedere al portale',
        'body_text' => 'Non riesco più ad accedere, potete aiutarmi?',
        'body_html' => '<p>Non riesco più ad accedere, potete aiutarmi?</p>',
        'received_at' => now(),
    ], $attributes));
}

test('mittente identificato senza match di thread crea un nuovo ticket helpdesk con il primo messaggio via email', function (): void {
    $requester = User::factory()->create(['email' => 'cliente@example.test']);

    $email = makeClassifiedInboundEmail();

    $result = ApplyInboundEmail::run($email);

    expect($result->status)->toBe(EmailStatus::Applied)
        ->and($result->ticket_id)->not->toBeNull();

    $ticket = Ticket::query()->findOrFail($result->ticket_id);

    expect($ticket->title)->toBe('Non riesco ad accedere al portale')
        ->and($ticket->type)->toBe(TicketType::Helpdesk)
        ->and($ticket->requester_id)->toBe($requester->id)
        ->and($ticket->messages)->toHaveCount(1);

    $message = $ticket->messages->first();

    expect($message->channel)->toBe(TicketMessageChannel::Email)
        ->and($message->author_id)->toBe($requester->id)
        ->and($message->body_html)->toContain('Non riesco più ad accedere');
});

test('un corpo solo testo viene convertito in HTML sicuro prima di essere pubblicato sul ticket', function (): void {
    User::factory()->create(['email' => 'cliente@example.test']);

    $email = makeClassifiedInboundEmail([
        'body_html' => null,
        'body_text' => "Riga 1\n<script>alert(1)</script>",
    ]);

    $result = ApplyInboundEmail::run($email);

    $message = Ticket::query()->findOrFail($result->ticket_id)->messages->first();

    expect($message->body_html)->toContain('Riga 1')
        ->and($message->body_html)->not->toContain('<script>');
});

test('mittente identificato con match di thread accoda un messaggio sul ticket esistente invece di crearne uno nuovo', function (): void {
    $requester = User::factory()->create(['email' => 'cliente@example.test']);

    $ticket = Ticket::create([
        'title' => 'Ticket esistente',
        'status' => TicketStatus::Todo,
        'status_changed_at' => now(),
        'requester_id' => $requester->id,
        'type' => TicketType::Helpdesk,
    ]);

    $email = makeClassifiedInboundEmail(['subject' => "[#{$ticket->id}] Ticket esistente"]);

    $result = ApplyInboundEmail::run($email);

    expect($result->status)->toBe(EmailStatus::Applied)
        ->and($result->ticket_id)->toBe($ticket->id)
        ->and(Ticket::count())->toBe(1)
        ->and($ticket->refresh()->messages)->toHaveCount(1);
});

test('la risposta del richiedente via email applica la transizione T7 (waiting torna a previous_status)', function (): void {
    $requester = User::factory()->create(['email' => 'cliente@example.test']);

    $ticket = Ticket::create([
        'title' => 'Ticket in attesa',
        'status' => TicketStatus::Waiting,
        'previous_status' => TicketStatus::Todo,
        'status_changed_at' => now(),
        'requester_id' => $requester->id,
        'type' => TicketType::Helpdesk,
    ]);

    $email = makeClassifiedInboundEmail(['subject' => "[#{$ticket->id}] Ticket in attesa"]);

    ApplyInboundEmail::run($email);

    expect($ticket->refresh()->status)->toBe(TicketStatus::Todo);
});

test('mittente non identificato non crea nessun ticket e lascia il messaggio in quarantena', function (): void {
    $email = makeClassifiedInboundEmail(['from_email' => 'mai-visto@example.test']);

    $result = ApplyInboundEmail::run($email);

    expect($result->status)->toBe(EmailStatus::Quarantined)
        ->and($result->ticket_id)->toBeNull()
        ->and(Ticket::count())->toBe(0);
});

test('un mittente sconosciuto senza soppressioni attive emette EmailQuarantined con auto-reply consentito', function (): void {
    Event::fake([EmailQuarantined::class]);

    $email = makeClassifiedInboundEmail(['from_email' => 'mai-visto@example.test']);

    ApplyInboundEmail::run($email);

    Event::assertDispatched(
        EmailQuarantined::class,
        fn (EmailQuarantined $event): bool => $event->emailMessage->is($email) && $event->autoReplyAllowed === true,
    );
});

test('un mittente sconosciuto già soppresso per rate limit (US-304) emette EmailQuarantined senza consentire l\'auto-reply', function (): void {
    Event::fake([EmailQuarantined::class]);

    EmailSuppression::create([
        'email' => 'mai-visto@example.test',
        'reason' => SuppressionReason::LoopProtection,
        'expires_at' => now()->addHours(24),
    ]);

    $email = makeClassifiedInboundEmail(['from_email' => 'mai-visto@example.test']);

    ApplyInboundEmail::run($email);

    Event::assertDispatched(
        EmailQuarantined::class,
        fn (EmailQuarantined $event): bool => $event->autoReplyAllowed === false,
    );
});

test('un fallimento nella notifica di quarantena non cambia lo stato del messaggio (problema 2 del v1)', function (): void {
    Event::listen(EmailQuarantined::class, function (): void {
        throw new RuntimeException('invio notifica falsamente non riuscito');
    });

    $email = makeClassifiedInboundEmail(['from_email' => 'mai-visto@example.test']);

    $result = ApplyInboundEmail::run($email);

    expect($result->status)->toBe(EmailStatus::Quarantined);
});

test('un messaggio già in quarantena viene riprocessato con successo una volta che il mittente diventa identificabile (US-322)', function (): void {
    $email = makeClassifiedInboundEmail([
        'from_email' => 'nuovo-cliente@example.test',
        'status' => EmailStatus::Quarantined,
    ]);

    // Simula l'esito dell'azione amministrativa "associa a utente esistente":
    // una volta che ResolveEmailSender può risolvere il mittente, la pipeline
    // riparte da sola richiamando di nuovo questa stessa Action.
    User::factory()->create(['email' => 'nuovo-cliente@example.test']);

    $result = ApplyInboundEmail::run($email);

    expect($result->status)->toBe(EmailStatus::Applied)
        ->and($result->ticket_id)->not->toBeNull();
});

test('un\'email non ancora classificata viene ignorata senza effetti', function (): void {
    User::factory()->create(['email' => 'cliente@example.test']);

    $email = makeClassifiedInboundEmail(['status' => EmailStatus::Received]);

    $result = ApplyInboundEmail::run($email);

    expect($result->status)->toBe(EmailStatus::Received)
        ->and($result->ticket_id)->toBeNull()
        ->and(Ticket::count())->toBe(0);
});

test('un fallimento nella risoluzione del ticket esistente annulla sia la creazione del messaggio sia l\'aggiornamento di email_messages', function (): void {
    User::factory()->create(['email' => 'cliente@example.test']);

    // Token subject che referenzia un ticket inesistente: la Action deve
    // fallire in modo controllato (status=failed), senza creare nulla.
    $email = makeClassifiedInboundEmail(['subject' => '[#999999] Ticket inesistente']);

    $result = ApplyInboundEmail::run($email);

    expect($result->status)->toBe(EmailStatus::Failed)
        ->and($result->failure_reason)->not->toBeNull()
        ->and(Ticket::count())->toBe(0)
        ->and(TicketMessage::count())->toBe(0);
});

test('un fallimento nella notifica post-commit non annulla il ticket/messaggio già creati (problema 2 del v1)', function (): void {
    User::factory()->create(['email' => 'cliente@example.test']);

    Event::listen(InboundEmailApplied::class, function (): void {
        throw new RuntimeException('invio notifica falsamente non riuscito');
    });

    $email = makeClassifiedInboundEmail();

    $result = ApplyInboundEmail::run($email);

    expect($result->status)->toBe(EmailStatus::Applied)
        ->and($result->ticket_id)->not->toBeNull();

    $ticket = Ticket::query()->findOrFail($result->ticket_id);

    expect($ticket->messages)->toHaveCount(1)
        ->and(EmailMessage::query()->findOrFail($result->id)->status)->toBe(EmailStatus::Applied);
});

test('gli allegati non inline del .eml grezzo vengono importati sul ticket_message creato (US-309)', function (): void {
    Storage::fake('raw-emails');
    Storage::fake('ticket-attachments');

    User::factory()->create(['email' => 'cliente@example.test']);

    $raw = file_get_contents(base_path('tests/Fixtures/emails/richiesta-con-allegati.eml'))
        ?: throw new RuntimeException('Fixture .eml mancante');
    $rawPath = Str::ulid()->toString().'.eml';
    Storage::disk('raw-emails')->put($rawPath, $raw);

    $email = makeClassifiedInboundEmail(['raw_path' => $rawPath]);

    $result = ApplyInboundEmail::run($email);

    expect($result->status)->toBe(EmailStatus::Applied);

    $message = Ticket::query()->findOrFail($result->ticket_id)->messages->first();

    expect($message->getMedia('attachments'))->toHaveCount(2)
        ->and(EmailAttachment::query()->where('email_message_id', $email->id)->where('status', EmailAttachmentStatus::Stored)->count())->toBe(2)
        ->and(EmailAttachment::query()->where('email_message_id', $email->id)->where('filename', 'logo.png')->exists())->toBeFalse();
});
