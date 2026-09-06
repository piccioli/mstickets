<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Mailables\TicketOpenedFromWebMail;
use App\Domain\Mail\Mailables\TicketReceivedByEmailMail;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

dataset('E1/E2 outbound ticket mailables', [
    'E1 TicketReceivedByEmailMail' => [TicketReceivedByEmailMail::class],
    'E2 TicketOpenedFromWebMail' => [TicketOpenedFromWebMail::class],
]);

function outboundNotificationForRequestBodyTest(Ticket $ticket): EmailMessage
{
    return EmailMessage::create([
        'direction' => EmailDirection::Outbound,
        'status' => EmailStatus::Queued,
        'from_email' => 'noreply@example.test',
        'ticket_id' => $ticket->id,
        'message_id' => 'notifica-test@example.test',
        'reply_to' => 'ticket+notifica-test@example.test',
        'subject' => "[#{$ticket->id}] {$ticket->title}",
    ]);
}

test('the email shows the title and the body of the first public message (the richiesta)', function (string $mailableClass): void {
    $requester = User::factory()->create();
    $ticket = ticket(['title' => 'Errore login SSO', 'requester_id' => $requester->id]);
    ticketMessage([
        'ticket_id' => $ticket->id,
        'author_id' => $requester->id,
        'body_html' => '<p>Da ieri non riesco ad accedere al portale con le mie credenziali.</p>',
        'body_text' => 'Da ieri non riesco ad accedere al portale con le mie credenziali.',
        'posted_at' => now(),
    ]);
    $outbound = outboundNotificationForRequestBodyTest($ticket);

    $mailable = new $mailableClass($ticket, $outbound);

    $html = $mailable->render();

    expect($html)
        ->toContain('Errore login SSO')
        ->toContain('Da ieri non riesco ad accedere al portale con le mie credenziali.');

    $content = $mailable->content();
    $text = view($content->text, [...$content->with, 'ticket' => $ticket, 'portalUrl' => 'https://example.test/tickets/1'])->render();

    expect($text)->toContain('Da ieri non riesco ad accedere al portale con le mie credenziali.');
})->with('E1/E2 outbound ticket mailables');

test('a ticket without any message renders the email without crashing', function (string $mailableClass): void {
    $requester = User::factory()->create();
    $ticket = ticket(['title' => 'Errore login SSO', 'requester_id' => $requester->id]);
    $outbound = outboundNotificationForRequestBodyTest($ticket);

    $mailable = new $mailableClass($ticket, $outbound);

    expect($mailable->render())->toContain('Errore login SSO');
})->with('E1/E2 outbound ticket mailables');
