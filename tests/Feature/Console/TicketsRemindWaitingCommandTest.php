<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Mailables\TicketWaitingReminderMail;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Ticketing\Enums\TicketLogEvent;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\TicketLog;
use App\Domain\Ticketing\Models\TicketView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

// Settimana di riferimento (stessa di WorkingDaysCalculatorTest): lunedì 2026-08-10,
// giovedì 2026-08-13 = 3 giorni lavorativi dopo (mar+mer+gio).

test('reminds the requester of a waiting ticket idle for at least the threshold', function (): void {
    Mail::fake();

    $this->travelTo('2026-08-10 10:00:00'); // Monday
    $requester = User::factory()->create();
    $ticket = ticket(['status' => TicketStatus::Waiting, 'requester_id' => $requester->id]);

    $this->travelTo('2026-08-13 10:00:00'); // Thursday
    Artisan::call('tickets:remind-waiting');

    Mail::assertQueued(
        TicketWaitingReminderMail::class,
        fn (TicketWaitingReminderMail $mail): bool => $mail->ticket->is($ticket),
    );
});

test('does not remind a ticket that has not been idle long enough', function (): void {
    Mail::fake();

    $this->travelTo('2026-08-10 10:00:00'); // Monday
    $requester = User::factory()->create();
    ticket(['status' => TicketStatus::Waiting, 'requester_id' => $requester->id]);

    $this->travelTo('2026-08-12 10:00:00'); // Wednesday, only 2 working days elapsed
    Artisan::call('tickets:remind-waiting');

    Mail::assertNothingQueued();
});

test('uses the most recent ticket_log as the last activity, not the ticket creation date', function (): void {
    Mail::fake();

    $this->travelTo('2026-08-10 10:00:00'); // Monday
    $requester = User::factory()->create();
    $ticket = ticket(['status' => TicketStatus::Waiting, 'requester_id' => $requester->id]);

    $this->travelTo('2026-08-12 09:00:00'); // Wednesday
    TicketLog::create([
        'ticket_id' => $ticket->id,
        'user_id' => $requester->id,
        'event' => TicketLogEvent::MessagePosted,
        'is_system' => false,
        'occurred_at' => now(),
    ]);

    $this->travelTo('2026-08-13 10:00:00'); // Thursday: only 1 working day since the log above
    Artisan::call('tickets:remind-waiting');

    Mail::assertNothingQueued();
});

test('uses the most recent ticket_view as the last activity', function (): void {
    Mail::fake();

    $this->travelTo('2026-08-10 10:00:00'); // Monday
    $requester = User::factory()->create();
    $ticket = ticket(['status' => TicketStatus::Waiting, 'requester_id' => $requester->id]);

    $this->travelTo('2026-08-12 09:00:00'); // Wednesday
    TicketView::create([
        'ticket_id' => $ticket->id,
        'user_id' => $requester->id,
        'viewed_on' => today()->toDateString(),
        'last_viewed_at' => now(),
        'view_count' => 1,
    ]);

    $this->travelTo('2026-08-13 10:00:00'); // Thursday: only 1 working day since the view above
    Artisan::call('tickets:remind-waiting');

    Mail::assertNothingQueued();
});

test('ignores tickets that are not status=waiting', function (): void {
    Mail::fake();

    $this->travelTo('2026-08-10 10:00:00'); // Monday
    $requester = User::factory()->create();
    ticket(['status' => TicketStatus::Progress, 'requester_id' => $requester->id]);

    $this->travelTo('2026-08-13 10:00:00'); // Thursday
    Artisan::call('tickets:remind-waiting');

    Mail::assertNothingQueued();
});

test('skips a ticket already reminded within the cooldown window', function (): void {
    Mail::fake();

    $this->travelTo('2026-08-10 10:00:00'); // Monday
    $requester = User::factory()->create();
    $ticket = ticket(['status' => TicketStatus::Waiting, 'requester_id' => $requester->id]);

    $this->travelTo('2026-08-13 10:00:00'); // Thursday: eligible, but already reminded below
    EmailMessage::create([
        'direction' => EmailDirection::Outbound,
        'status' => EmailStatus::Queued,
        'from_email' => 'noreply@example.test',
        'ticket_id' => $ticket->id,
        'message_id' => 'promemoria-precedente@example.test',
        'reply_to' => 'ticket+promemoria-precedente@example.test',
        'subject' => "[#{$ticket->id}] {$ticket->title}",
        'mailable_class' => TicketWaitingReminderMail::class,
    ]);

    Artisan::call('tickets:remind-waiting');

    Mail::assertNothingQueued();
});

test('does not fail and sends no mail when an eligible ticket has no requester', function (): void {
    Mail::fake();

    $this->travelTo('2026-08-10 10:00:00'); // Monday
    ticket(['status' => TicketStatus::Waiting]);

    $this->travelTo('2026-08-13 10:00:00'); // Thursday
    $exitCode = Artisan::call('tickets:remind-waiting');

    expect($exitCode)->toBe(0);
    Mail::assertNothingQueued();
});
