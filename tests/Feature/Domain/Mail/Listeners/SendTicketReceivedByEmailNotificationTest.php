<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Events\InboundEmailApplied;
use App\Domain\Mail\Listeners\SendTicketReceivedByEmailNotification;
use App\Domain\Mail\Mailables\TicketReceivedByEmailMail;
use App\Domain\Mail\Models\EmailMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('sends E1 when the inbound email applied a new ticket', function (): void {
    Mail::fake();

    $requester = User::factory()->create(['email' => 'cliente@example.test']);
    $ticket = ticket(['title' => 'Non riesco ad accedere', 'requester_id' => $requester->id]);
    $emailMessage = EmailMessage::create([
        'direction' => EmailDirection::Inbound,
        'from_email' => 'cliente@example.test',
        'status' => EmailStatus::Applied,
        'subject' => 'Non riesco ad accedere',
    ]);

    (new SendTicketReceivedByEmailNotification)->handle(new InboundEmailApplied($ticket, $emailMessage, isNewTicket: true));

    Mail::assertQueued(TicketReceivedByEmailMail::class, fn (TicketReceivedByEmailMail $mail): bool => $mail->ticket->is($ticket));
});

test('does not send E1 when the inbound email applied an existing ticket', function (): void {
    Mail::fake();

    $requester = User::factory()->create(['email' => 'cliente@example.test']);
    $ticket = ticket(['title' => 'Non riesco ad accedere', 'requester_id' => $requester->id]);
    $emailMessage = EmailMessage::create([
        'direction' => EmailDirection::Inbound,
        'from_email' => 'cliente@example.test',
        'status' => EmailStatus::Applied,
        'subject' => 'Non riesco ad accedere',
    ]);

    (new SendTicketReceivedByEmailNotification)->handle(new InboundEmailApplied($ticket, $emailMessage, isNewTicket: false));

    Mail::assertNothingQueued();
});

test('does not send E1 when the ticket has no requester', function (): void {
    Mail::fake();

    $ticket = ticket(['title' => 'Non riesco ad accedere']);
    $emailMessage = EmailMessage::create([
        'direction' => EmailDirection::Inbound,
        'from_email' => 'cliente@example.test',
        'status' => EmailStatus::Applied,
        'subject' => 'Non riesco ad accedere',
    ]);

    (new SendTicketReceivedByEmailNotification)->handle(new InboundEmailApplied($ticket, $emailMessage, isNewTicket: true));

    Mail::assertNothingQueued();
});
