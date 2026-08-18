<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Mail\Actions\SendTicketStatusChangedMail;
use App\Domain\Mail\Mailables\TicketStatusChangedMail;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Events\TicketStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('notifies requester, assignee and tester but excludes the actor who made the change', function (): void {
    Mail::fake();

    $requester = withRole(User::factory()->create(), UserRole::Customer);
    $assignee = withRole(User::factory()->create(), UserRole::Developer);
    $tester = withRole(User::factory()->create(), UserRole::Developer);
    $ticket = ticket([
        'requester_id' => $requester->id,
        'assignee_id' => $assignee->id,
        'tester_id' => $tester->id,
    ]);

    SendTicketStatusChangedMail::run(new TicketStatusChanged($ticket, TicketStatus::Todo, TicketStatus::Progress, $assignee));

    Mail::assertQueued(TicketStatusChangedMail::class, 2);
    Mail::assertQueued(TicketStatusChangedMail::class, fn (TicketStatusChangedMail $mail): bool => $mail->newStatus === TicketStatus::Progress
        && $mail->ticket->is($ticket));
});

test('marks the recipient as customer only for a user with the customer role', function (): void {
    Mail::fake();

    $requester = withRole(User::factory()->create(), UserRole::Customer);
    $assignee = withRole(User::factory()->create(), UserRole::Developer);
    $ticket = ticket(['requester_id' => $requester->id, 'assignee_id' => $assignee->id]);

    SendTicketStatusChangedMail::run(new TicketStatusChanged($ticket, TicketStatus::Todo, TicketStatus::Progress, $assignee));

    Mail::assertQueued(
        TicketStatusChangedMail::class,
        fn (TicketStatusChangedMail $mail): bool => $mail->recipientIsCustomer,
    );
});

test('does not notify anyone when the only relevant recipient is the actor', function (): void {
    Mail::fake();

    $assignee = withRole(User::factory()->create(), UserRole::Developer);
    $ticket = ticket(['assignee_id' => $assignee->id]);

    SendTicketStatusChangedMail::run(new TicketStatusChanged($ticket, TicketStatus::Todo, TicketStatus::Progress, $assignee));

    Mail::assertNothingQueued();
});

dataset('relevant status transitions', [
    'Todo -> Progress' => [TicketStatus::Todo, TicketStatus::Progress],
    'Progress -> Waiting' => [TicketStatus::Progress, TicketStatus::Waiting],
    'Testing -> Rejected' => [TicketStatus::Testing, TicketStatus::Rejected],
    'Tested -> Released' => [TicketStatus::Tested, TicketStatus::Released],
]);

test('sends the new status to the requester for each relevant transition', function (TicketStatus $from, TicketStatus $to): void {
    Mail::fake();

    $requester = withRole(User::factory()->create(), UserRole::Customer);
    $actor = withRole(User::factory()->create(), UserRole::Developer);
    $ticket = ticket(['requester_id' => $requester->id, 'status' => $to]);

    SendTicketStatusChangedMail::run(new TicketStatusChanged($ticket, $from, $to, $actor));

    Mail::assertQueued(
        TicketStatusChangedMail::class,
        fn (TicketStatusChangedMail $mail): bool => $mail->previousStatus === $from && $mail->newStatus === $to,
    );
})->with('relevant status transitions');
