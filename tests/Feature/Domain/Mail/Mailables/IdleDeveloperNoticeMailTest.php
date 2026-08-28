<?php

declare(strict_types=1);

use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Mailables\IdleDeveloperNoticeMail;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Ticketing\Enums\TicketStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function outboundIdleDeveloperNoticeNotification(): EmailMessage
{
    $token = Str::random(8);

    return EmailMessage::create([
        'direction' => EmailDirection::Outbound,
        'status' => EmailStatus::Queued,
        'from_email' => 'noreply@example.test',
        'message_id' => "idle-developer-notice-test-{$token}@example.test",
        'reply_to' => "ticket+idle-developer-notice-test-{$token}@example.test",
        'subject' => 'You have tickets waiting to be picked up',
    ]);
}

test('renders well-formed HTML listing every idle ticket with its status', function (): void {
    $ticketA = ticket(['title' => 'Errore login SSO', 'status' => TicketStatus::Todo]);
    $ticketB = ticket(['title' => 'Richiesta nuova funzionalità', 'status' => TicketStatus::Waiting]);

    $tickets = new Collection([$ticketA, $ticketB]);

    $mailable = new IdleDeveloperNoticeMail($tickets, outboundIdleDeveloperNoticeNotification());
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
        ->and($html)->toContain(mb_strtoupper(TicketStatus::Todo->getLabel()))
        ->and($html)->toContain(mb_strtoupper(TicketStatus::Waiting->getLabel()));
});

test('sets the Message-Id header and the VERP Reply-To from the outbound email_messages row', function (): void {
    $ticket = ticket();
    $tickets = new Collection([$ticket]);
    $outbound = outboundIdleDeveloperNoticeNotification();

    $mailable = new IdleDeveloperNoticeMail($tickets, $outbound);

    expect($mailable->envelope()->replyTo[0]->address)->toBe($outbound->reply_to)
        ->and($mailable->headers()->messageId)->toBe($outbound->message_id);
});

test('generates a plain-text version alongside the HTML', function (): void {
    $ticket = ticket(['title' => 'Errore login SSO']);
    $tickets = new Collection([$ticket]);

    $mailable = new IdleDeveloperNoticeMail($tickets, outboundIdleDeveloperNoticeNotification());
    $content = $mailable->content();
    $textOnly = view($content->text, $content->with)->render();

    expect($textOnly)
        ->not->toBeEmpty()
        ->toContain('Errore login SSO')
        ->not->toContain('<p>');
});

test('renders the body in the language set via ->locale(), never a raw untranslated key (§7.6, US-320)', function (): void {
    $ticket = ticket();
    $tickets = new Collection([$ticket]);

    $italianHtml = (new IdleDeveloperNoticeMail($tickets, outboundIdleDeveloperNoticeNotification()))->locale('it')->render();
    $englishHtml = (new IdleDeveloperNoticeMail($tickets, outboundIdleDeveloperNoticeNotification()))->locale('en')->render();

    expect($italianHtml)->toContain('Hai ticket assegnati senza nulla attualmente in lavorazione.')
        ->and($englishHtml)->toContain('You have tickets assigned with nothing currently in progress.')
        ->and($englishHtml)->not->toContain('Hai ticket assegnati');
});
