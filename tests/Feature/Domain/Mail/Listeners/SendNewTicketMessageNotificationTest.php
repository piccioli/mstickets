<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Mail\Listeners\SendNewTicketMessageNotification;
use App\Domain\Mail\Mailables\NewTicketMessageMail;
use App\Domain\Ticketing\Enums\TicketMessageVisibility;
use App\Domain\Ticketing\Events\TicketMessagePosted;
use App\Domain\Ticketing\Models\TicketMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('sends E5 to the ticket recipients when a public message is posted', function (): void {
    Mail::fake();

    $requester = withRole(User::factory()->create(), UserRole::Customer);
    $assignee = withRole(User::factory()->create(), UserRole::Developer);
    $ticket = ticket(['requester_id' => $requester->id, 'assignee_id' => $assignee->id]);
    $message = TicketMessage::create([
        'ticket_id' => $ticket->id,
        'author_id' => $assignee->id,
        'channel' => 'web',
        'visibility' => TicketMessageVisibility::Public,
        'body_html' => '<p>Aggiornamento</p>',
        'posted_at' => now(),
    ])->fresh();

    (new SendNewTicketMessageNotification)->handle(new TicketMessagePosted($ticket, $message));

    Mail::assertQueued(NewTicketMessageMail::class);
});

test('does not send E5 when the posted message is internal', function (): void {
    Mail::fake();

    $requester = withRole(User::factory()->create(), UserRole::Customer);
    $assignee = withRole(User::factory()->create(), UserRole::Developer);
    $ticket = ticket(['requester_id' => $requester->id, 'assignee_id' => $assignee->id]);
    $message = TicketMessage::create([
        'ticket_id' => $ticket->id,
        'author_id' => $assignee->id,
        'channel' => 'web',
        'visibility' => TicketMessageVisibility::Internal,
        'body_html' => '<p>Nota interna</p>',
        'posted_at' => now(),
    ])->fresh();

    (new SendNewTicketMessageNotification)->handle(new TicketMessagePosted($ticket, $message));

    Mail::assertNothingQueued();
});

test('implements ShouldQueue so the send happens asynchronously', function (): void {
    expect(new SendNewTicketMessageNotification)->toBeInstanceOf(ShouldQueue::class);
});
