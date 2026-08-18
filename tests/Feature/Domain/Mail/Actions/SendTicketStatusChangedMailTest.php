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

test('testing to rejected notifies both the assignee and the requester', function (): void {
    Mail::fake();

    $requester = withRole(User::factory()->create(), UserRole::Customer);
    $assignee = withRole(User::factory()->create(), UserRole::Developer);
    $tester = withRole(User::factory()->create(), UserRole::Developer);
    $ticket = ticket([
        'requester_id' => $requester->id,
        'assignee_id' => $assignee->id,
        'tester_id' => $tester->id,
    ]);

    SendTicketStatusChangedMail::run(new TicketStatusChanged($ticket, TicketStatus::Testing, TicketStatus::Rejected, $tester));

    Mail::assertQueued(TicketStatusChangedMail::class, 2);
    Mail::assertQueued(TicketStatusChangedMail::class, fn (TicketStatusChangedMail $mail): bool => $mail->hasTo($requester->email));
    Mail::assertQueued(TicketStatusChangedMail::class, fn (TicketStatusChangedMail $mail): bool => $mail->hasTo($assignee->email));
});

test('excludes the actor even when the table would otherwise notify them', function (): void {
    Mail::fake();

    $assignee = withRole(User::factory()->create(), UserRole::Developer);
    $ticket = ticket(['assignee_id' => $assignee->id]);

    // Testing -> Tested notifica l'assegnatario: se l'attore è proprio l'assegnatario, nessuna email.
    SendTicketStatusChangedMail::run(new TicketStatusChanged($ticket, TicketStatus::Testing, TicketStatus::Tested, $assignee));

    Mail::assertNothingQueued();
});

test('a transition absent from the table sends no notification at all', function (): void {
    Mail::fake();

    $requester = withRole(User::factory()->create(), UserRole::Customer);
    $assignee = withRole(User::factory()->create(), UserRole::Developer);
    $actor = withRole(User::factory()->create(), UserRole::Manager);
    $ticket = ticket(['requester_id' => $requester->id, 'assignee_id' => $assignee->id]);

    // Todo -> Progress non ha nessuna voce "notifica X" in §6.1.3 (solo il demote).
    SendTicketStatusChangedMail::run(new TicketStatusChanged($ticket, TicketStatus::Todo, TicketStatus::Progress, $actor));

    Mail::assertNothingQueued();
});

test('marks the recipient as customer only for a user with the customer role', function (): void {
    Mail::fake();

    $requester = withRole(User::factory()->create(), UserRole::Customer);
    $actor = withRole(User::factory()->create(), UserRole::Manager);
    $ticket = ticket(['requester_id' => $requester->id]);

    SendTicketStatusChangedMail::run(new TicketStatusChanged($ticket, TicketStatus::New, TicketStatus::Rejected, $actor));

    Mail::assertQueued(
        TicketStatusChangedMail::class,
        fn (TicketStatusChangedMail $mail): bool => $mail->recipientIsCustomer,
    );
});

test('problem notifies every active manager except the actor', function (): void {
    Mail::fake();

    $actingManager = withRole(User::factory()->create(), UserRole::Manager);
    $otherManager = withRole(User::factory()->create(), UserRole::Manager);
    withRole(User::factory()->create(), UserRole::Developer);
    $ticket = ticket(['problem_reason' => 'Bloccato da un fornitore esterno']);

    SendTicketStatusChangedMail::run(new TicketStatusChanged($ticket, TicketStatus::Progress, TicketStatus::Problem, $actingManager));

    Mail::assertQueued(TicketStatusChangedMail::class, 1);
    Mail::assertQueued(TicketStatusChangedMail::class, fn (TicketStatusChangedMail $mail): bool => $mail->hasTo($otherManager->email));
    Mail::assertNotQueued(TicketStatusChangedMail::class, fn (TicketStatusChangedMail $mail): bool => $mail->hasTo($actingManager->email));
});

dataset('table-driven transitions and their expected recipient roles', [
    'new -> rejected notifies the requester' => [TicketStatus::New, TicketStatus::Rejected, 'requester'],
    'progress -> testing notifies the tester' => [TicketStatus::Progress, TicketStatus::Testing, 'tester'],
    'testing -> tested notifies the assignee' => [TicketStatus::Testing, TicketStatus::Tested, 'assignee'],
    'testing -> todo notifies the assignee (failed test)' => [TicketStatus::Testing, TicketStatus::Todo, 'assignee'],
    'progress -> waiting notifies the requester' => [TicketStatus::Progress, TicketStatus::Waiting, 'requester'],
    'backlog -> rejected falls back to the catch-all and notifies the requester' => [TicketStatus::Backlog, TicketStatus::Rejected, 'requester'],
]);

test('sends the notification to the expected role for each table-driven transition', function (TicketStatus $from, TicketStatus $to, string $expectedRole): void {
    Mail::fake();

    $requester = withRole(User::factory()->create(), UserRole::Customer);
    $assignee = withRole(User::factory()->create(), UserRole::Developer);
    $tester = withRole(User::factory()->create(), UserRole::Developer);
    $actor = withRole(User::factory()->create(), UserRole::Manager);
    $ticket = ticket([
        'requester_id' => $requester->id,
        'assignee_id' => $assignee->id,
        'tester_id' => $tester->id,
        'waiting_reason' => 'In attesa di un fornitore esterno',
    ]);

    $expectedRecipient = match ($expectedRole) {
        'requester' => $requester,
        'assignee' => $assignee,
        'tester' => $tester,
    };

    SendTicketStatusChangedMail::run(new TicketStatusChanged($ticket, $from, $to, $actor));

    Mail::assertQueued(TicketStatusChangedMail::class, 1);
    Mail::assertQueued(TicketStatusChangedMail::class, fn (TicketStatusChangedMail $mail): bool => $mail->hasTo($expectedRecipient->email)
        && $mail->previousStatus === $from
        && $mail->newStatus === $to);
})->with('table-driven transitions and their expected recipient roles');
