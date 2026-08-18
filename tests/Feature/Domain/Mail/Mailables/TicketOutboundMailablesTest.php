<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Mailables\NewCustomerTicketStaffMail;
use App\Domain\Mail\Mailables\TicketOpenedFromWebMail;
use App\Domain\Mail\Mailables\TicketReceivedByEmailMail;
use App\Domain\Mail\Mailables\TicketWaitingReminderMail;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Ticketing\Enums\TicketStatus as TicketStatusEnum;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function outboundNotificationFor(Ticket $ticket): EmailMessage
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

dataset('outbound ticket mailables', [
    'E1 TicketReceivedByEmailMail' => [TicketReceivedByEmailMail::class],
    'E2 TicketOpenedFromWebMail' => [TicketOpenedFromWebMail::class],
    'E3 NewCustomerTicketStaffMail' => [NewCustomerTicketStaffMail::class],
    'E7 TicketWaitingReminderMail' => [TicketWaitingReminderMail::class],
]);

test('renders well-formed HTML with the shared layout components and no parse errors', function (string $mailableClass): void {
    $requester = User::factory()->create();
    $ticket = ticket(['title' => 'Errore login SSO', 'status' => TicketStatusEnum::New, 'requester_id' => $requester->id]);
    $outbound = outboundNotificationFor($ticket);

    $mailable = new $mailableClass($ticket, $outbound);

    $html = $mailable->render();

    libxml_use_internal_errors(true);
    $document = new DOMDocument;
    $document->loadHTML($html);
    $errors = libxml_get_errors();
    libxml_clear_errors();

    expect($errors)->toBeEmpty()
        ->and($html)->toContain('Ticket #'.$ticket->id)
        ->and($html)->toContain('Errore login SSO');
})->with('outbound ticket mailables');

test('sets the Message-Id header and the VERP Reply-To from the outbound email_messages row', function (string $mailableClass): void {
    $requester = User::factory()->create();
    $ticket = ticket(['title' => 'Errore login SSO', 'requester_id' => $requester->id]);
    $outbound = outboundNotificationFor($ticket);

    $mailable = new $mailableClass($ticket, $outbound);

    expect($mailable->envelope()->replyTo[0]->address)->toBe('ticket+notifica-test@example.test')
        ->and($mailable->headers()->messageId)->toBe('notifica-test@example.test')
        ->and($mailable->envelope()->subject)->toBe("[#{$ticket->id}] Errore login SSO");
})->with('outbound ticket mailables');

test('generates a plain-text version alongside the HTML', function (string $mailableClass): void {
    $requester = User::factory()->create();
    $ticket = ticket(['title' => 'Errore login SSO', 'requester_id' => $requester->id]);
    $outbound = outboundNotificationFor($ticket);

    $mailable = new $mailableClass($ticket, $outbound);
    $content = $mailable->content();

    $text = view($content->text, [...$content->with, 'ticket' => $ticket, 'portalUrl' => 'https://example.test/tickets/1'])->render();

    expect($text)
        ->not->toBeEmpty()
        ->toContain('Errore login SSO')
        ->not->toContain('<p>');
})->with('outbound ticket mailables');
