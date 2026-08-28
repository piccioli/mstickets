<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Mail\Enums\NotificationType;
use App\Domain\Mail\Mailables\MailDigestMail;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Mail\Models\EmailSuppression;
use App\Domain\Mail\Models\NotificationPreference;
use App\Domain\Ticketing\Enums\TicketMessageVisibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('sends a digest to a customer with activity on one of their tickets', function (): void {
    Mail::fake();

    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $staff = User::factory()->create();
    $ticket = ticket(['requester_id' => $customer->id]);
    ticketMessage(['ticket_id' => $ticket->id, 'author_id' => $staff->id, 'visibility' => TicketMessageVisibility::Public, 'posted_at' => now()]);

    Artisan::call('mail:send-digest');

    Mail::assertQueued(
        MailDigestMail::class,
        fn (MailDigestMail $mail): bool => $mail->entries->count() === 1 && $mail->entries->first()->ticket->is($ticket),
    );
});

test('sends no digest to a customer without activity in the last 24h', function (): void {
    Mail::fake();

    $customer = withRole(User::factory()->create(), UserRole::Customer);
    ticket(['requester_id' => $customer->id]);

    Artisan::call('mail:send-digest');

    Mail::assertNothingQueued();
});

test('does not send to a customer who has already received a digest today', function (): void {
    Mail::fake();

    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $staff = User::factory()->create();
    $ticket = ticket(['requester_id' => $customer->id]);
    ticketMessage(['ticket_id' => $ticket->id, 'author_id' => $staff->id, 'visibility' => TicketMessageVisibility::Public, 'posted_at' => now()]);

    EmailMessage::create([
        'direction' => 'outbound',
        'status' => 'queued',
        'from_email' => 'noreply@example.test',
        'user_id' => $customer->id,
        'message_id' => 'digest-precedente@example.test',
        'reply_to' => 'ticket+digest-precedente@example.test',
        'subject' => 'Digest',
        'mailable_class' => MailDigestMail::class,
    ]);

    Artisan::call('mail:send-digest');

    Mail::assertNothingQueued();
});

test('respects a customer having disabled the E8 notification preference', function (): void {
    Mail::fake();

    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $staff = User::factory()->create();
    $ticket = ticket(['requester_id' => $customer->id]);
    ticketMessage(['ticket_id' => $ticket->id, 'author_id' => $staff->id, 'visibility' => TicketMessageVisibility::Public, 'posted_at' => now()]);

    NotificationPreference::create([
        'user_id' => $customer->id,
        'notification_type' => NotificationType::MailDigest->value,
        'channel' => 'email',
        'enabled' => false,
    ]);

    Artisan::call('mail:send-digest');

    Mail::assertNothingQueued();
});

test('respects an active email suppression for the customer', function (): void {
    Mail::fake();

    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $staff = User::factory()->create();
    $ticket = ticket(['requester_id' => $customer->id]);
    ticketMessage(['ticket_id' => $ticket->id, 'author_id' => $staff->id, 'visibility' => TicketMessageVisibility::Public, 'posted_at' => now()]);

    EmailSuppression::create([
        'email' => $customer->email,
        'reason' => 'hard_bounce',
        'bounce_count' => 1,
    ]);

    Artisan::call('mail:send-digest');

    Mail::assertNothingQueued();
});

test('does not write or send anything in dry-run mode', function (): void {
    Mail::fake();

    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $staff = User::factory()->create();
    $ticket = ticket(['requester_id' => $customer->id]);
    ticketMessage(['ticket_id' => $ticket->id, 'author_id' => $staff->id, 'visibility' => TicketMessageVisibility::Public, 'posted_at' => now()]);

    Artisan::call('mail:send-digest', ['--dry-run' => true]);

    Mail::assertNothingQueued();
    expect(EmailMessage::query()->count())->toBe(0);
});

test('does not fail and sends no mail when there are no customers', function (): void {
    Mail::fake();

    $exitCode = Artisan::call('mail:send-digest');

    expect($exitCode)->toBe(0);
    Mail::assertNothingQueued();
});
