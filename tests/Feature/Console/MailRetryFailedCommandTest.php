<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailMessageLogEvent;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Enums\SuppressionReason;
use App\Domain\Mail\Mailables\NewCustomerTicketStaffMail;
use App\Domain\Mail\Mailables\TicketStatusChangedMail;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Mail\Models\EmailSuppression;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function failedOutboundEmail(array $overrides = []): EmailMessage
{
    $recipient = $overrides['user_id'] ?? null;

    return EmailMessage::query()->forceCreate(array_merge([
        'ulid' => strtolower((string) Str::ulid()),
        'direction' => EmailDirection::Outbound,
        'message_id' => Str::uuid()->toString().'@example.test',
        'from_email' => 'staff@example.test',
        'to' => ['destinatario@example.test'],
        'subject' => 'Notifica',
        'status' => EmailStatus::Failed,
        'mailable_class' => NewCustomerTicketStaffMail::class,
    ], $overrides));
}

test('riaccoda tutti i messaggi outbound falliti', function (): void {
    Mail::fake();

    $recipient = User::factory()->create();
    $ticketRecord = ticket(['requester_id' => $recipient->id]);
    $email = failedOutboundEmail(['ticket_id' => $ticketRecord->id, 'user_id' => $recipient->id, 'to' => [$recipient->email]]);

    $exitCode = Artisan::call('mail:retry-failed');

    expect($exitCode)->toBe(0)
        ->and($email->refresh()->status)->toBe(EmailStatus::Queued);

    Mail::assertQueued(NewCustomerTicketStaffMail::class);
});

test('registra User::system() come attore nel log', function (): void {
    Mail::fake();

    $recipient = User::factory()->create();
    $ticketRecord = ticket(['requester_id' => $recipient->id]);
    $email = failedOutboundEmail(['ticket_id' => $ticketRecord->id, 'user_id' => $recipient->id, 'to' => [$recipient->email]]);

    Artisan::call('mail:retry-failed');

    $log = $email->refresh()->logs()->sole();
    expect($log->action)->toBe(EmailMessageLogEvent::Resent)
        ->and($log->user_id)->toBe(User::system()->id);
});

test('rispetta --limit e lascia intatti i messaggi oltre soglia', function (): void {
    Mail::fake();

    $recipient = User::factory()->create();
    $ticketRecord = ticket(['requester_id' => $recipient->id]);
    $first = failedOutboundEmail(['ticket_id' => $ticketRecord->id, 'user_id' => $recipient->id, 'to' => [$recipient->email]]);
    $this->travel(1)->second();
    $second = failedOutboundEmail(['ticket_id' => $ticketRecord->id, 'user_id' => $recipient->id, 'to' => [$recipient->email]]);

    Artisan::call('mail:retry-failed', ['--limit' => 1]);

    expect($first->refresh()->status)->toBe(EmailStatus::Queued)
        ->and($second->refresh()->status)->toBe(EmailStatus::Failed);
});

test('un destinatario in soppressione blocca il reinvio ma il comando prosegue con gli altri', function (): void {
    Mail::fake();

    $suppressed = User::factory()->create(['email' => 'soppresso@example.test']);
    $recipient = User::factory()->create();
    $ticketRecord = ticket(['requester_id' => $recipient->id]);

    EmailSuppression::create(['email' => 'soppresso@example.test', 'reason' => SuppressionReason::HardBounce]);

    $blocked = failedOutboundEmail(['ticket_id' => $ticketRecord->id, 'user_id' => $suppressed->id, 'to' => [$suppressed->email]]);
    $resent = failedOutboundEmail(['ticket_id' => $ticketRecord->id, 'user_id' => $recipient->id, 'to' => [$recipient->email]]);

    $exitCode = Artisan::call('mail:retry-failed');

    expect($exitCode)->toBe(0)
        ->and($blocked->refresh()->status)->toBe(EmailStatus::Failed)
        ->and($resent->refresh()->status)->toBe(EmailStatus::Queued);

    Mail::assertQueued(NewCustomerTicketStaffMail::class, 1);
});

test('un mailable non ricostruibile viene segnalato senza fermare gli altri', function (): void {
    Mail::fake();

    $recipient = User::factory()->create();
    $ticketRecord = ticket(['requester_id' => $recipient->id]);

    $unresendable = failedOutboundEmail([
        'ticket_id' => $ticketRecord->id,
        'user_id' => $recipient->id,
        'to' => [$recipient->email],
        'mailable_class' => TicketStatusChangedMail::class,
    ]);
    $resent = failedOutboundEmail(['ticket_id' => $ticketRecord->id, 'user_id' => $recipient->id, 'to' => [$recipient->email]]);

    $exitCode = Artisan::call('mail:retry-failed');

    expect($exitCode)->toBe(0)
        ->and($unresendable->refresh()->status)->toBe(EmailStatus::Failed)
        ->and($resent->refresh()->status)->toBe(EmailStatus::Queued);
});

test('--email-message reinvia solo il messaggio indicato', function (): void {
    Mail::fake();

    $recipient = User::factory()->create();
    $ticketRecord = ticket(['requester_id' => $recipient->id]);
    $targeted = failedOutboundEmail(['ticket_id' => $ticketRecord->id, 'user_id' => $recipient->id, 'to' => [$recipient->email]]);
    $other = failedOutboundEmail(['ticket_id' => $ticketRecord->id, 'user_id' => $recipient->id, 'to' => [$recipient->email]]);

    $exitCode = Artisan::call('mail:retry-failed', ['--email-message' => $targeted->ulid]);

    expect($exitCode)->toBe(0)
        ->and($targeted->refresh()->status)->toBe(EmailStatus::Queued)
        ->and($other->refresh()->status)->toBe(EmailStatus::Failed);
});

test('--email-message con ulid inesistente fallisce esplicitamente', function (): void {
    $exitCode = Artisan::call('mail:retry-failed', ['--email-message' => strtolower((string) Str::ulid())]);

    expect($exitCode)->toBe(1);
});

test('ignora i messaggi non falliti o non outbound', function (): void {
    Mail::fake();

    $recipient = User::factory()->create();
    $ticketRecord = ticket(['requester_id' => $recipient->id]);
    $sent = failedOutboundEmail(['ticket_id' => $ticketRecord->id, 'user_id' => $recipient->id, 'status' => EmailStatus::Sent]);
    $inbound = failedOutboundEmail(['ticket_id' => $ticketRecord->id, 'direction' => EmailDirection::Inbound, 'status' => EmailStatus::Failed]);

    Artisan::call('mail:retry-failed');

    expect($sent->refresh()->status)->toBe(EmailStatus::Sent)
        ->and($inbound->refresh()->status)->toBe(EmailStatus::Failed);

    Mail::assertNothingQueued();
});
