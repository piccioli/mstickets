<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Mail\Actions\AssignEmailMessageSender;
use App\Domain\Mail\Actions\CreateEmailSenderAndAssign;
use App\Domain\Mail\Actions\DiscardEmailMessage;
use App\Domain\Mail\Actions\LinkInboundEmailToTicket;
use App\Domain\Mail\Actions\ReprocessInboundEmailMessage;
use App\Domain\Mail\Actions\RetryOutboundEmailMessage;
use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailMessageLogEvent;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Enums\SuppressionReason;
use App\Domain\Mail\Mailables\NewCustomerTicketStaffMail;
use App\Domain\Mail\Mailables\TicketStatusChangedMail;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Mail\Models\EmailSuppression;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function quarantinedEmail(array $attributes = []): EmailMessage
{
    return EmailMessage::create(array_merge([
        'direction' => EmailDirection::Inbound,
        'status' => EmailStatus::Quarantined,
        'from_email' => 'sconosciuto@example.test',
        'from_name' => 'Mario Sconosciuto',
        'subject' => 'Ho un problema',
        'body_text' => 'Aiuto!',
        'received_at' => now(),
    ], $attributes))->fresh();
}

test('riprocessa rilancia la pipeline da classified e traccia l\'azione', function (): void {
    $requester = User::factory()->create(['email' => 'cliente@example.test']);
    $actor = User::factory()->create();

    $email = EmailMessage::create([
        'direction' => EmailDirection::Inbound,
        'status' => EmailStatus::Failed,
        'from_email' => 'cliente@example.test',
        'subject' => 'Richiesta di supporto',
        'body_html' => '<p>Aiuto</p>',
        'received_at' => now(),
    ])->fresh();

    $result = ReprocessInboundEmailMessage::run($email, $actor);

    expect($result->status)->toBe(EmailStatus::Applied)
        ->and($result->ticket_id)->not->toBeNull();

    $ticket = Ticket::query()->findOrFail($result->ticket_id);
    expect($ticket->requester_id)->toBe($requester->id);

    $log = $result->logs()->sole();
    expect($log->action)->toBe(EmailMessageLogEvent::Reprocessed)
        ->and($log->user_id)->toBe($actor->id);
});

test('riprocessare uno stato non ammesso fallisce esplicitamente', function (): void {
    $actor = User::factory()->create();
    $email = EmailMessage::create([
        'direction' => EmailDirection::Inbound,
        'status' => EmailStatus::Received,
        'from_email' => 'cliente@example.test',
    ])->fresh();

    ReprocessInboundEmailMessage::run($email, $actor);
})->throws(RuntimeException::class);

test('assegna a utente esistente riporta il messaggio a classified e crea il ticket', function (): void {
    $sender = User::factory()->create(['email' => 'nuovo@example.test']);
    $actor = User::factory()->create();

    $email = quarantinedEmail();

    $result = AssignEmailMessageSender::run($email, $sender, $actor);

    expect($result->status)->toBe(EmailStatus::Applied)
        ->and($result->user_id)->toBe($sender->id)
        ->and($result->ticket_id)->not->toBeNull();

    $log = $result->logs()->sole();
    expect($log->action)->toBe(EmailMessageLogEvent::SenderAssigned);
});

test('assegnare un mittente a un messaggio non in quarantena fallisce', function (): void {
    $sender = User::factory()->create();
    $actor = User::factory()->create();
    $email = quarantinedEmail(['status' => EmailStatus::Classified]);

    AssignEmailMessageSender::run($email, $sender, $actor);
})->throws(RuntimeException::class);

test('crea nuovo utente e ticket crea l\'utente col ruolo customer e riprocessa', function (): void {
    Role::query()->firstOrCreate(['name' => UserRole::Customer->value, 'guard_name' => 'web']);

    $actor = User::factory()->create();
    $email = quarantinedEmail(['from_email' => 'cliente.nuovo@example.test', 'from_name' => 'Cliente Nuovo']);

    $result = CreateEmailSenderAndAssign::run($email, 'Cliente Nuovo', 'cliente.nuovo@example.test', $actor);

    expect($result->status)->toBe(EmailStatus::Applied);

    $sender = User::query()->where('email', 'cliente.nuovo@example.test')->sole();
    expect($sender->hasRole(UserRole::Customer->value))->toBeTrue()
        ->and($result->user_id)->toBe($sender->id);

    $log = $result->logs()->sole();
    expect($log->action)->toBe(EmailMessageLogEvent::SenderCreated);
});

test('collega a ticket sposta un messaggio già applicato su un altro ticket', function (): void {
    $sender = User::factory()->create(['email' => 'cliente@example.test']);
    $actor = User::factory()->create();

    $originTicket = ticket(['requester_id' => $sender->id]);
    $targetTicket = ticket(['requester_id' => $sender->id]);

    $email = EmailMessage::create([
        'direction' => EmailDirection::Inbound,
        'status' => EmailStatus::Applied,
        'from_email' => 'cliente@example.test',
        'user_id' => $sender->id,
        'ticket_id' => $originTicket->id,
        'body_html' => '<p>Messaggio</p>',
        'received_at' => now(),
    ])->fresh();

    $message = ticketMessage([
        'ticket_id' => $originTicket->id,
        'author_id' => $sender->id,
        'channel' => 'email',
        'email_message_id' => $email->id,
    ]);

    $result = LinkInboundEmailToTicket::run($email, $targetTicket, $actor);

    expect($result->ticket_id)->toBe($targetTicket->id)
        ->and($message->fresh()->ticket_id)->toBe($targetTicket->id);

    $originTicket->refresh();
    expect($originTicket->messages)->toHaveCount(0);

    $log = $result->logs()->sole();
    expect($log->action)->toBe(EmailMessageLogEvent::LinkedToTicket);
});

test('collega a ticket pubblica un nuovo messaggio quando l\'email non è ancora applicata', function (): void {
    $sender = User::factory()->create(['email' => 'cliente@example.test']);
    $actor = User::factory()->create();
    $targetTicket = ticket(['requester_id' => $sender->id]);

    $email = EmailMessage::create([
        'direction' => EmailDirection::Inbound,
        'status' => EmailStatus::Classified,
        'from_email' => 'cliente@example.test',
        'user_id' => $sender->id,
        'body_html' => '<p>Messaggio senza thread risolto</p>',
        'received_at' => now(),
    ])->fresh();

    $result = LinkInboundEmailToTicket::run($email, $targetTicket, $actor);

    expect($result->status)->toBe(EmailStatus::Applied)
        ->and($result->ticket_id)->toBe($targetTicket->id)
        ->and($targetTicket->messages()->count())->toBe(1);
});

test('collega a ticket senza mittente risolto fallisce esplicitamente', function (): void {
    $actor = User::factory()->create();
    $targetTicket = ticket();

    $email = quarantinedEmail();

    LinkInboundEmailToTicket::run($email, $targetTicket, $actor);
})->throws(RuntimeException::class);

test('scarta forza discarded con un motivo manuale e traccia l\'azione', function (): void {
    $actor = User::factory()->create();
    $email = quarantinedEmail(['status' => EmailStatus::Classified]);

    $result = DiscardEmailMessage::run($email, 'Spam confermato manualmente', $actor);

    expect($result->status)->toBe(EmailStatus::Discarded)
        ->and($result->failure_reason)->toBe('Spam confermato manualmente');

    $log = $result->logs()->sole();
    expect($log->action)->toBe(EmailMessageLogEvent::Discarded)
        ->and($log->notes)->toBe('Spam confermato manualmente');
});

test('scartare un\'email già collegata a un ticket fallisce', function (): void {
    $actor = User::factory()->create();
    $ticketRecord = ticket();
    $email = quarantinedEmail(['status' => EmailStatus::Applied, 'ticket_id' => $ticketRecord->id]);

    DiscardEmailMessage::run($email, 'motivo qualunque', $actor);
})->throws(RuntimeException::class);

test('reinvia riaccoda un mailable ricostruibile e riporta lo stato a queued', function (): void {
    Mail::fake();

    $recipient = User::factory()->create();
    $actor = User::factory()->create();
    $ticketRecord = ticket(['requester_id' => $recipient->id]);

    $outbound = EmailMessage::query()->forceCreate([
        'ulid' => strtolower((string) Str::ulid()),
        'direction' => EmailDirection::Outbound,
        'message_id' => 'abc@example.test',
        'ticket_id' => $ticketRecord->id,
        'user_id' => $recipient->id,
        'from_email' => 'staff@example.test',
        'to' => [$recipient->email],
        'subject' => 'Nuovo ticket cliente',
        'status' => EmailStatus::Failed,
        'mailable_class' => NewCustomerTicketStaffMail::class,
    ]);

    $result = RetryOutboundEmailMessage::run($outbound, $actor);

    expect($result->status)->toBe(EmailStatus::Queued);

    Mail::assertQueued(NewCustomerTicketStaffMail::class);

    $log = $result->logs()->sole();
    expect($log->action)->toBe(EmailMessageLogEvent::Resent);
});

test('reinvia non fa nulla se il destinatario è finito in soppressione', function (): void {
    Mail::fake();

    $recipient = User::factory()->create(['email' => 'soppresso@example.test']);
    $actor = User::factory()->create();
    $ticketRecord = ticket(['requester_id' => $recipient->id]);

    EmailSuppression::create(['email' => 'soppresso@example.test', 'reason' => SuppressionReason::HardBounce]);

    $outbound = EmailMessage::query()->forceCreate([
        'ulid' => strtolower((string) Str::ulid()),
        'direction' => EmailDirection::Outbound,
        'message_id' => 'def@example.test',
        'ticket_id' => $ticketRecord->id,
        'user_id' => $recipient->id,
        'from_email' => 'staff@example.test',
        'to' => [$recipient->email],
        'subject' => 'Notifica',
        'status' => EmailStatus::Bounced,
        'mailable_class' => NewCustomerTicketStaffMail::class,
    ]);

    $result = RetryOutboundEmailMessage::run($outbound, $actor);

    expect($result->status)->toBe(EmailStatus::Bounced);

    Mail::assertNothingQueued();

    $log = $result->logs()->sole();
    expect($log->action)->toBe(EmailMessageLogEvent::ResendBlocked);
});

test('reinvia fallisce esplicitamente per un mailable non ricostruibile', function (): void {
    Mail::fake();

    $recipient = User::factory()->create();
    $actor = User::factory()->create();
    $ticketRecord = ticket(['requester_id' => $recipient->id]);

    $outbound = EmailMessage::query()->forceCreate([
        'ulid' => strtolower((string) Str::ulid()),
        'direction' => EmailDirection::Outbound,
        'message_id' => 'ghi@example.test',
        'ticket_id' => $ticketRecord->id,
        'user_id' => $recipient->id,
        'from_email' => 'staff@example.test',
        'to' => [$recipient->email],
        'subject' => 'Cambio di stato',
        'status' => EmailStatus::Failed,
        'mailable_class' => TicketStatusChangedMail::class,
    ]);

    RetryOutboundEmailMessage::run($outbound, $actor);
})->throws(RuntimeException::class);
