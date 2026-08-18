<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Mailables\NewTicketMessageMail;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function outboundNewMessageNotificationFor(Ticket $ticket): EmailMessage
{
    return EmailMessage::create([
        'direction' => EmailDirection::Outbound,
        'status' => EmailStatus::Queued,
        'from_email' => 'noreply@example.test',
        'ticket_id' => $ticket->id,
        'message_id' => 'notifica-nuovo-messaggio-test@example.test',
        'reply_to' => 'ticket+notifica-nuovo-messaggio-test@example.test',
        'subject' => "[#{$ticket->id}] Nuovo messaggio",
    ]);
}

test('renders well-formed HTML with the shared layout components and no parse errors', function (): void {
    $requester = User::factory()->create();
    $ticket = ticket(['title' => 'Errore login SSO', 'requester_id' => $requester->id]);
    $outbound = outboundNewMessageNotificationFor($ticket);

    $mailable = new NewTicketMessageMail($ticket, 'Mario Rossi', '<p>Grazie, controllo subito.</p>', now(), $outbound);

    $html = $mailable->render();

    libxml_use_internal_errors(true);
    $document = new DOMDocument;
    $document->loadHTML($html);
    $errors = libxml_get_errors();
    libxml_clear_errors();

    expect($errors)->toBeEmpty()
        ->and($html)->toContain('Ticket #'.$ticket->id)
        ->and($html)->toContain('Errore login SSO')
        ->and($html)->toContain('Mario Rossi')
        ->and($html)->toContain('Grazie, controllo subito.');
});

test('sets the Message-Id header and the VERP Reply-To from the outbound email_messages row', function (): void {
    $ticket = ticket(['title' => 'Errore login SSO']);
    $outbound = outboundNewMessageNotificationFor($ticket);

    $mailable = new NewTicketMessageMail($ticket, 'Mario Rossi', '<p>Grazie</p>', now(), $outbound);

    expect($mailable->envelope()->replyTo[0]->address)->toBe('ticket+notifica-nuovo-messaggio-test@example.test')
        ->and($mailable->headers()->messageId)->toBe('notifica-nuovo-messaggio-test@example.test');
});

test('generates a plain-text version alongside the HTML', function (): void {
    $ticket = ticket(['title' => 'Errore login SSO']);
    $outbound = outboundNewMessageNotificationFor($ticket);

    $mailable = new NewTicketMessageMail($ticket, 'Mario Rossi', '<p>Grazie, controllo subito.</p>', now(), $outbound);
    $content = $mailable->content();

    $text = view($content->text, [...$content->with, 'ticket' => $ticket, 'portalUrl' => 'https://example.test/tickets/1'])->render();

    expect($text)
        ->not->toBeEmpty()
        ->toContain('Errore login SSO')
        ->toContain('Mario Rossi')
        ->toContain('Grazie, controllo subito.')
        ->not->toContain('<p>');
});
