<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Actions\SendTicketWaitingReminderMail;
use App\Domain\Mail\Mailables\TicketWaitingReminderMail;
use App\Domain\Ticketing\Enums\TicketStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('queues TicketWaitingReminderMail to the requester', function (): void {
    Mail::fake();

    $requester = User::factory()->create();
    $ticket = ticket(['status' => TicketStatus::Waiting, 'requester_id' => $requester->id]);

    SendTicketWaitingReminderMail::run($ticket);

    Mail::assertQueued(
        TicketWaitingReminderMail::class,
        fn (TicketWaitingReminderMail $mail): bool => $mail->ticket->is($ticket),
    );
});

test('does nothing when the ticket has no requester', function (): void {
    Mail::fake();

    $ticket = ticket(['status' => TicketStatus::Waiting]);

    SendTicketWaitingReminderMail::run($ticket);

    Mail::assertNothingQueued();
});
