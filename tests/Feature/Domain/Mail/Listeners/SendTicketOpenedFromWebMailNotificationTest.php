<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Listeners\SendTicketOpenedFromWebMailNotification;
use App\Domain\Mail\Mailables\TicketOpenedFromWebMail;
use App\Domain\Ticketing\Enums\TicketMessageChannel;
use App\Domain\Ticketing\Events\TicketCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('sends E2 when the ticket was created from the web channel', function (): void {
    Mail::fake();

    $requester = User::factory()->create(['email' => 'cliente@example.test']);
    $ticket = ticket(['title' => 'Vorrei una nuova funzionalità', 'requester_id' => $requester->id]);

    (new SendTicketOpenedFromWebMailNotification)->handle(new TicketCreated($ticket, TicketMessageChannel::Web));

    Mail::assertQueued(TicketOpenedFromWebMail::class, fn (TicketOpenedFromWebMail $mail): bool => $mail->ticket->is($ticket));
});

test('does not send E2 when the ticket was created from the email channel', function (): void {
    Mail::fake();

    $requester = User::factory()->create(['email' => 'cliente@example.test']);
    $ticket = ticket(['title' => 'Non riesco ad accedere', 'requester_id' => $requester->id]);

    (new SendTicketOpenedFromWebMailNotification)->handle(new TicketCreated($ticket, TicketMessageChannel::Email));

    Mail::assertNothingQueued();
});

test('does not send E2 when the ticket has no requester', function (): void {
    Mail::fake();

    $ticket = ticket(['title' => 'Ticket senza richiedente']);

    (new SendTicketOpenedFromWebMailNotification)->handle(new TicketCreated($ticket, TicketMessageChannel::Web));

    Mail::assertNothingQueued();
});
