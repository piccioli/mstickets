<?php

declare(strict_types=1);

use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Support\TicketMessageSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Mail\ExampleTicketNotificationMail;

uses(RefreshDatabase::class);

/**
 * @return array{ticket: Ticket, authorName: string, occurredAt: DateTimeImmutable, bodyHtml: string, ctaLabel: string, ctaUrl: string}
 */
function exampleTicketNotificationData(): array
{
    return [
        'ticket' => ticket(['title' => 'Errore login SSO', 'status' => TicketStatus::Progress]),
        'authorName' => 'Mario Rossi',
        'occurredAt' => new DateTimeImmutable('2026-03-05 10:15:00'),
        'bodyHtml' => TicketMessageSanitizer::sanitize('<p>Non riesco più ad accedere con lo <strong>SSO</strong>.</p><script>alert(1)</script>'),
        'ctaLabel' => 'Apri il ticket',
        'ctaUrl' => 'https://tickets.montagnaservizi.com/admin/tickets/1',
    ];
}

function exampleTicketNotificationMail(): ExampleTicketNotificationMail
{
    $data = exampleTicketNotificationData();

    return new ExampleTicketNotificationMail(...$data);
}

test('the shared layout renders well-formed HTML with no parse errors for a real Mailable', function (): void {
    $html = exampleTicketNotificationMail()->render();

    libxml_use_internal_errors(true);
    $document = new DOMDocument;
    $document->loadHTML($html);
    $errors = libxml_get_errors();
    libxml_clear_errors();

    expect($errors)->toBeEmpty();
    expect($html)->toContain('<!doctype html>');
});

test('the rendered email contains every reusable component: header, badge, message block, CTA, footer', function (): void {
    $html = exampleTicketNotificationMail()->render();

    expect($html)
        ->toContain('Ticket #')
        ->toContain('Errore login SSO')
        ->toContain(mb_strtoupper(TicketStatus::Progress->getLabel()))
        ->toContain('Mario Rossi')
        ->toContain('SSO')
        ->not->toContain('<script>')
        ->toContain('Apri il ticket')
        ->toContain('https://tickets.montagnaservizi.com/admin/tickets/1')
        ->toContain('Montagna Servizi SCPA')
        ->toContain('P.IVA 11790660960');
});

test('the plain-text version is generated alongside the HTML and carries the same content', function (): void {
    $text = view('emails.examples.ticket-notification-text', exampleTicketNotificationData())->render();

    expect($text)
        ->not->toBeEmpty()
        ->toContain('Errore login SSO')
        ->toContain('Mario Rossi')
        ->toContain('SSO')
        ->toContain('Apri il ticket: https://tickets.montagnaservizi.com/admin/tickets/1')
        ->toContain('Montagna Servizi SCPA')
        ->not->toContain('<p>')
        ->not->toContain('<strong>');
});

test('the footer hides the notification preferences link when no URL is configured', function (): void {
    config(['mail_pipeline.notification_preferences_url' => '']);

    $html = exampleTicketNotificationMail()->render();

    expect($html)->not->toContain('Gestisci le preferenze di notifica');
});

test('the footer shows the notification preferences link when a URL is configured', function (): void {
    config(['mail_pipeline.notification_preferences_url' => 'https://tickets.montagnaservizi.com/preferenze']);

    $html = exampleTicketNotificationMail()->render();

    expect($html)
        ->toContain('Gestisci le preferenze di notifica')
        ->toContain('https://tickets.montagnaservizi.com/preferenze');
});
