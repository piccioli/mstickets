<?php

declare(strict_types=1);

use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Mailables\UnknownSenderStaffMail;
use App\Domain\Mail\Models\EmailMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function outboundStaffNotification(): EmailMessage
{
    return EmailMessage::create([
        'direction' => EmailDirection::Outbound,
        'status' => EmailStatus::Queued,
        'from_email' => 'noreply@example.test',
        'message_id' => 'notifica-test@example.test',
        'reply_to' => 'ticket+notifica-test@example.test',
        'subject' => 'Mittente sconosciuto: sconosciuto@example.test',
    ]);
}

function quarantinedMessageForMailable(): EmailMessage
{
    return EmailMessage::create([
        'direction' => EmailDirection::Inbound,
        'from_email' => 'sconosciuto@example.test',
        'status' => EmailStatus::Quarantined,
        'subject' => 'Ho un problema',
        'body_text' => 'Non riesco ad accedere al portale, potete aiutarmi?',
    ]);
}

test('renders well-formed HTML with the shared layout and the quarantined message details', function (): void {
    $mailable = new UnknownSenderStaffMail(quarantinedMessageForMailable(), outboundStaffNotification());

    $html = $mailable->render();

    libxml_use_internal_errors(true);
    $document = new DOMDocument;
    $document->loadHTML($html);
    $errors = libxml_get_errors();
    libxml_clear_errors();

    expect($errors)->toBeEmpty()
        ->and($html)->toContain('sconosciuto@example.test')
        ->and($html)->toContain('Ho un problema')
        ->and($html)->toContain('Non riesco ad accedere al portale');
});

test('sets the Message-Id header and the VERP Reply-To from the outbound email_messages row', function (): void {
    $outbound = outboundStaffNotification();
    $mailable = new UnknownSenderStaffMail(quarantinedMessageForMailable(), $outbound);

    expect($mailable->envelope()->replyTo[0]->address)->toBe('ticket+notifica-test@example.test')
        ->and($mailable->headers()->messageId)->toBe('notifica-test@example.test');
});

test('hides the quarantine link when no review URL is configured', function (): void {
    config(['mail_pipeline.quarantine_review_url' => '']);

    $mailable = new UnknownSenderStaffMail(quarantinedMessageForMailable(), outboundStaffNotification());

    expect($mailable->render())->not->toContain('Vai alla quarantena');
});

test('shows a direct link to the quarantine row when a review URL is configured', function (): void {
    config(['mail_pipeline.quarantine_review_url' => 'https://admin.example.test/quarantena']);

    $quarantined = quarantinedMessageForMailable();
    $mailable = new UnknownSenderStaffMail($quarantined, outboundStaffNotification());

    $html = $mailable->render();

    expect($html)->toContain('Vai alla quarantena')
        ->and($html)->toContain("https://admin.example.test/quarantena/{$quarantined->ulid}");
});

test('generates a plain-text version alongside the HTML', function (): void {
    config(['mail_pipeline.quarantine_review_url' => 'https://admin.example.test/quarantena']);

    $mailable = new UnknownSenderStaffMail(quarantinedMessageForMailable(), outboundStaffNotification());
    $content = $mailable->content();

    $text = view($content->text, $content->with)->render();

    expect($text)
        ->not->toBeEmpty()
        ->toContain('sconosciuto@example.test')
        ->toContain('Vai alla quarantena')
        ->not->toContain('<p>');
});
