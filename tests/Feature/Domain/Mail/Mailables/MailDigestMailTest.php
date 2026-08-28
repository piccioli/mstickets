<?php

declare(strict_types=1);

use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Mailables\MailDigestMail;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Mail\Support\TicketDigestEntry;
use App\Domain\Ticketing\Enums\TicketStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function outboundDigestNotification(): EmailMessage
{
    $token = Str::random(8);

    return EmailMessage::create([
        'direction' => EmailDirection::Outbound,
        'status' => EmailStatus::Queued,
        'from_email' => 'noreply@example.test',
        'message_id' => "digest-test-{$token}@example.test",
        'reply_to' => "ticket+digest-test-{$token}@example.test",
        'subject' => 'Your daily ticket activity digest',
    ]);
}

test('renders well-formed HTML listing every ticket entry with its message count and status change', function (): void {
    $ticketA = ticket(['title' => 'Errore login SSO']);
    $ticketB = ticket(['title' => 'Richiesta nuova funzionalità', 'status' => TicketStatus::Done]);

    $entries = new Collection([
        new TicketDigestEntry($ticketA, 2, null, null),
        new TicketDigestEntry($ticketB, 0, TicketStatus::Released, TicketStatus::Done),
    ]);

    $mailable = new MailDigestMail($entries, outboundDigestNotification());
    $html = $mailable->render();

    libxml_use_internal_errors(true);
    $document = new DOMDocument;
    $document->loadHTML($html);
    $errors = libxml_get_errors();
    libxml_clear_errors();

    expect($errors)->toBeEmpty()
        ->and($html)->toContain('Ticket #'.$ticketA->id)
        ->and($html)->toContain('Errore login SSO')
        ->and($html)->toContain('Ticket #'.$ticketB->id)
        ->and($html)->toContain('Richiesta nuova funzionalità')
        ->and($html)->toContain(mb_strtoupper(TicketStatus::Released->getLabel()))
        ->and($html)->toContain(mb_strtoupper(TicketStatus::Done->getLabel()));
});

test('sets the Message-Id header and the VERP Reply-To from the outbound email_messages row', function (): void {
    $ticket = ticket();
    $entries = new Collection([new TicketDigestEntry($ticket, 1, null, null)]);
    $outbound = outboundDigestNotification();

    $mailable = new MailDigestMail($entries, $outbound);

    expect($mailable->envelope()->replyTo[0]->address)->toBe($outbound->reply_to)
        ->and($mailable->headers()->messageId)->toBe($outbound->message_id);
});

test('generates a plain-text version alongside the HTML', function (): void {
    $ticket = ticket(['title' => 'Errore login SSO']);
    $entries = new Collection([new TicketDigestEntry($ticket, 3, null, null)]);

    $mailable = new MailDigestMail($entries, outboundDigestNotification());
    $content = $mailable->content();
    $textOnly = view($content->text, $content->with)->render();

    expect($textOnly)
        ->not->toBeEmpty()
        ->toContain('Errore login SSO')
        ->not->toContain('<p>');
});

test('renders the body in the language set via ->locale(), never a raw untranslated key (§7.6, US-320)', function (): void {
    $ticket = ticket();
    $entries = new Collection([new TicketDigestEntry($ticket, 1, null, null)]);

    $italianHtml = (new MailDigestMail($entries, outboundDigestNotification()))->locale('it')->render();
    $englishHtml = (new MailDigestMail($entries, outboundDigestNotification()))->locale('en')->render();

    expect($italianHtml)->toContain('Ecco un riepilogo dell')
        ->and($italianHtml)->toContain('ultime 24 ore')
        ->and($englishHtml)->toContain('Here is a summary of the activity on your tickets in the last 24 hours.')
        ->and($englishHtml)->not->toContain('Ecco un riepilogo');
});
