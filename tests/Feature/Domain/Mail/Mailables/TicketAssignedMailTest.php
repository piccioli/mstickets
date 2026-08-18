<?php

declare(strict_types=1);

use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Mailables\TicketAssignedMail;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function outboundAssignedNotificationFor(Ticket $ticket): EmailMessage
{
    return EmailMessage::create([
        'direction' => EmailDirection::Outbound,
        'status' => EmailStatus::Queued,
        'from_email' => 'noreply@example.test',
        'ticket_id' => $ticket->id,
        'message_id' => 'notifica-assegnazione-test@example.test',
        'reply_to' => 'ticket+notifica-assegnazione-test@example.test',
        'subject' => "[#{$ticket->id}] Ticket assegnato",
    ]);
}

test('renders well-formed HTML with the shared layout components and no parse errors', function (): void {
    $ticket = ticket(['title' => 'Errore login SSO']);
    $outbound = outboundAssignedNotificationFor($ticket);

    $mailable = new TicketAssignedMail($ticket, false, $outbound);

    $html = $mailable->render();

    libxml_use_internal_errors(true);
    $document = new DOMDocument;
    $document->loadHTML($html);
    $errors = libxml_get_errors();
    libxml_clear_errors();

    expect($errors)->toBeEmpty()
        ->and($html)->toContain('Ticket #'.$ticket->id);
});

test('sets the Message-Id header and the VERP Reply-To from the outbound email_messages row', function (): void {
    $ticket = ticket(['title' => 'Errore login SSO']);
    $outbound = outboundAssignedNotificationFor($ticket);

    $mailable = new TicketAssignedMail($ticket, false, $outbound);

    expect($mailable->envelope()->replyTo[0]->address)->toBe('ticket+notifica-assegnazione-test@example.test')
        ->and($mailable->headers()->messageId)->toBe('notifica-assegnazione-test@example.test');
});

test('generates a plain-text version alongside the HTML', function (): void {
    $ticket = ticket(['title' => 'Errore login SSO']);
    $outbound = outboundAssignedNotificationFor($ticket);

    $mailable = new TicketAssignedMail($ticket, false, $outbound);
    $content = $mailable->content();

    $text = view($content->text, [...$content->with, 'ticket' => $ticket, 'portalUrl' => 'https://example.test/tickets/1'])->render();

    expect($text)
        ->not->toBeEmpty()
        ->toContain('Errore login SSO')
        ->not->toContain('<p>');
});

test('shows different wording for a tester assignment than for a developer assignment', function (): void {
    $ticket = ticket(['title' => 'Errore login SSO']);
    $outbound = outboundAssignedNotificationFor($ticket);

    $developerHtml = (new TicketAssignedMail($ticket, false, $outbound))->render();
    $testerHtml = (new TicketAssignedMail($ticket, true, $outbound))->render();

    expect($testerHtml)->toContain('tester')
        ->and($developerHtml)->not->toContain('tester');
});
