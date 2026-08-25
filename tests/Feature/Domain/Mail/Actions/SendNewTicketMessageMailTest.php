<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Mail\Actions\SendNewTicketMessageMail;
use App\Domain\Mail\Mailables\NewTicketMessageMail;
use App\Domain\Ticketing\Enums\TicketMessageVisibility;
use App\Domain\Ticketing\Events\TicketMessagePosted;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/**
 * @param  array<string, mixed>  $attributes
 */
function publicMessageOn(Ticket $ticket, User $author, array $attributes = []): TicketMessage
{
    return TicketMessage::create(array_merge([
        'ticket_id' => $ticket->id,
        'author_id' => $author->id,
        'channel' => 'web',
        'visibility' => TicketMessageVisibility::Public,
        'body_html' => '<p>Ciao, potete aiutarmi?</p>',
        'posted_at' => now(),
    ], $attributes))->fresh();
}

test('notifies requester, assignee and tester but excludes the author of the message', function (): void {
    Mail::fake();

    $requester = withRole(User::factory()->create(), UserRole::Customer);
    $assignee = withRole(User::factory()->create(), UserRole::Developer);
    $tester = withRole(User::factory()->create(), UserRole::Developer);
    $ticket = ticket([
        'requester_id' => $requester->id,
        'assignee_id' => $assignee->id,
        'tester_id' => $tester->id,
    ]);
    $message = publicMessageOn($ticket, $assignee);

    SendNewTicketMessageMail::run(new TicketMessagePosted($ticket, $message));

    Mail::assertQueued(NewTicketMessageMail::class, 2);
    Mail::assertQueued(NewTicketMessageMail::class, fn (NewTicketMessageMail $mail): bool => $mail->ticket->is($ticket)
        && $mail->authorName === $assignee->name);
});

test('never sends anything for an internal message, not even to staff', function (): void {
    Mail::fake();

    $requester = withRole(User::factory()->create(), UserRole::Customer);
    $assignee = withRole(User::factory()->create(), UserRole::Developer);
    $ticket = ticket(['requester_id' => $requester->id, 'assignee_id' => $assignee->id]);
    $message = publicMessageOn($ticket, $assignee, ['visibility' => TicketMessageVisibility::Internal]);

    SendNewTicketMessageMail::run(new TicketMessagePosted($ticket, $message));

    Mail::assertNothingQueued();
});

test('does not notify anyone when the only relevant recipient is the author', function (): void {
    Mail::fake();

    $assignee = withRole(User::factory()->create(), UserRole::Developer);
    $ticket = ticket(['assignee_id' => $assignee->id]);
    $message = publicMessageOn($ticket, $assignee);

    SendNewTicketMessageMail::run(new TicketMessagePosted($ticket, $message));

    Mail::assertNothingQueued();
});

test('includes participants in addition to requester, assignee and tester', function (): void {
    Mail::fake();

    $requester = withRole(User::factory()->create(), UserRole::Customer);
    $assignee = withRole(User::factory()->create(), UserRole::Developer);
    $participant = withRole(User::factory()->create(), UserRole::Developer);
    $ticket = ticket(['requester_id' => $requester->id, 'assignee_id' => $assignee->id]);
    $ticket->participants()->attach($participant->id);
    $message = publicMessageOn($ticket, $assignee);

    SendNewTicketMessageMail::run(new TicketMessagePosted($ticket, $message->fresh()));

    Mail::assertQueued(NewTicketMessageMail::class, 2);
});
