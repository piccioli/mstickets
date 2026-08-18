<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Actions\SendOutboundTicketMail;
use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Enums\NotificationType;
use App\Domain\Mail\Enums\SuppressionReason;
use App\Domain\Mail\Mailables\TicketReceivedByEmailMail;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Mail\Models\EmailSuppression;
use App\Domain\Mail\Models\NotificationPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function sendOutboundExampleMail(User $recipient): EmailMessage
{
    $ticket = ticket(['title' => 'Errore login', 'requester_id' => $recipient->id]);

    return SendOutboundTicketMail::run(
        ticket: $ticket,
        recipient: $recipient,
        notificationType: NotificationType::TicketReceivedByEmail,
        subject: "[#{$ticket->id}] {$ticket->title}",
        mailableClass: TicketReceivedByEmailMail::class,
        mailableFactory: fn (EmailMessage $outbound): TicketReceivedByEmailMail => new TicketReceivedByEmailMail($ticket, $outbound),
    );
}

test('creates an outbound email_messages row with a generated message_id and a VERP reply-to built from the same ulid', function (): void {
    Mail::fake();

    config(['mail_pipeline.support_address' => 'supporto@montagnaservizi.test']);

    $recipient = User::factory()->create(['email' => 'cliente@example.test']);

    $outbound = sendOutboundExampleMail($recipient);

    expect($outbound->direction)->toBe(EmailDirection::Outbound)
        ->and($outbound->status)->toBe(EmailStatus::Queued)
        ->and($outbound->message_id)->toBe("{$outbound->ulid}@montagnaservizi.test")
        ->and($outbound->reply_to)->toBe("ticket+{$outbound->ulid}@montagnaservizi.test")
        ->and($outbound->to)->toBe(['cliente@example.test'])
        ->and($outbound->mailable_class)->toBe(TicketReceivedByEmailMail::class);
});

test('queues the mailable when the recipient is not suppressed and has no disabled preference', function (): void {
    Mail::fake();

    $recipient = User::factory()->create(['email' => 'cliente@example.test']);

    sendOutboundExampleMail($recipient);

    Mail::assertQueued(TicketReceivedByEmailMail::class);
});

test('does not queue the mailable and marks the row suppressed when the recipient email is in email_suppressions', function (): void {
    Mail::fake();

    $recipient = User::factory()->create(['email' => 'cliente@example.test']);
    EmailSuppression::create(['email' => 'cliente@example.test', 'reason' => SuppressionReason::HardBounce]);

    $outbound = sendOutboundExampleMail($recipient);

    expect($outbound->status)->toBe(EmailStatus::Suppressed)
        ->and($outbound->failure_reason)->not->toBeNull();

    Mail::assertNothingQueued();
});

test('does not queue the mailable when the recipient disabled this notification type', function (): void {
    Mail::fake();

    $recipient = User::factory()->create(['email' => 'cliente@example.test']);
    NotificationPreference::create([
        'user_id' => $recipient->id,
        'notification_type' => NotificationType::TicketReceivedByEmail->value,
        'channel' => 'email',
        'enabled' => false,
    ]);

    $outbound = sendOutboundExampleMail($recipient);

    expect($outbound->status)->toBe(EmailStatus::Suppressed);

    Mail::assertNothingQueued();
});

test('sends when a notification preference row exists but is enabled', function (): void {
    Mail::fake();

    $recipient = User::factory()->create(['email' => 'cliente@example.test']);
    NotificationPreference::create([
        'user_id' => $recipient->id,
        'notification_type' => NotificationType::TicketReceivedByEmail->value,
        'channel' => 'email',
        'enabled' => true,
    ]);

    sendOutboundExampleMail($recipient);

    Mail::assertQueued(TicketReceivedByEmailMail::class);
});

test('falls back to the mail.from.address domain when no support address is configured', function (): void {
    Mail::fake();

    config(['mail_pipeline.support_address' => '', 'mail.from.address' => 'hello@example.com']);

    $recipient = User::factory()->create(['email' => 'cliente@example.test']);

    $outbound = sendOutboundExampleMail($recipient);

    expect($outbound->reply_to)->toBe("ticket+{$outbound->ulid}@example.com");
});
