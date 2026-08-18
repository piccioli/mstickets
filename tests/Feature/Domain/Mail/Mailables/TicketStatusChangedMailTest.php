<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Mailables\TicketStatusChangedMail;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function outboundStatusChangeNotificationFor(Ticket $ticket): EmailMessage
{
    return EmailMessage::create([
        'direction' => EmailDirection::Outbound,
        'status' => EmailStatus::Queued,
        'from_email' => 'noreply@example.test',
        'ticket_id' => $ticket->id,
        'message_id' => 'notifica-stato-test@example.test',
        'reply_to' => 'ticket+notifica-stato-test@example.test',
        'subject' => "[#{$ticket->id}] Stato aggiornato",
    ]);
}

test('renders well-formed HTML with the shared layout components and no parse errors', function (): void {
    $requester = User::factory()->create();
    $ticket = ticket(['title' => 'Errore login SSO', 'status' => TicketStatus::Progress, 'requester_id' => $requester->id]);
    $outbound = outboundStatusChangeNotificationFor($ticket);

    $mailable = new TicketStatusChangedMail($ticket, TicketStatus::Todo, TicketStatus::Progress, true, $outbound);

    $html = $mailable->render();

    libxml_use_internal_errors(true);
    $document = new DOMDocument;
    $document->loadHTML($html);
    $errors = libxml_get_errors();
    libxml_clear_errors();

    expect($errors)->toBeEmpty()
        ->and($html)->toContain('Ticket #'.$ticket->id)
        ->and($html)->toContain(mb_strtoupper(TicketStatus::Progress->getLabel()));
});

test('sets the Message-Id header and the VERP Reply-To from the outbound email_messages row', function (): void {
    $ticket = ticket(['title' => 'Errore login SSO']);
    $outbound = outboundStatusChangeNotificationFor($ticket);

    $mailable = new TicketStatusChangedMail($ticket, TicketStatus::Todo, TicketStatus::Progress, false, $outbound);

    expect($mailable->envelope()->replyTo[0]->address)->toBe('ticket+notifica-stato-test@example.test')
        ->and($mailable->headers()->messageId)->toBe('notifica-stato-test@example.test');
});

test('generates a plain-text version alongside the HTML', function (): void {
    $ticket = ticket(['title' => 'Errore login SSO']);
    $outbound = outboundStatusChangeNotificationFor($ticket);

    $mailable = new TicketStatusChangedMail($ticket, TicketStatus::Todo, TicketStatus::Progress, false, $outbound);
    $content = $mailable->content();

    $text = view($content->text, [...$content->with, 'ticket' => $ticket, 'portalUrl' => 'https://example.test/tickets/1'])->render();

    expect($text)
        ->not->toBeEmpty()
        ->toContain('Errore login SSO')
        ->not->toContain('<p>');
});

test('shows different wording for a customer recipient than for a staff recipient', function (): void {
    $requester = User::factory()->create(['name' => 'Mario Rossi']);
    $ticket = ticket(['title' => 'Errore login SSO', 'requester_id' => $requester->id]);
    $outbound = outboundStatusChangeNotificationFor($ticket);

    $customerHtml = (new TicketStatusChangedMail($ticket, TicketStatus::Todo, TicketStatus::Progress, true, $outbound))->render();
    $staffHtml = (new TicketStatusChangedMail($ticket, TicketStatus::Todo, TicketStatus::Progress, false, $outbound))->render();

    expect($customerHtml)->toContain('Il tuo ticket')
        ->and($staffHtml)->not->toContain('Il tuo ticket')
        ->and($staffHtml)->toContain('Mario Rossi');
});
