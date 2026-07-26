<?php

declare(strict_types=1);

use App\Domain\Ticketing\Support\TicketMessageSanitizer;

test('keeps allowlisted formatting elements and attributes', function (): void {
    $html = '<p>Ciao <strong>mondo</strong>, vedi <a href="https://example.com" title="Esempio">qui</a>.</p>';

    expect(TicketMessageSanitizer::sanitize($html))
        ->toContain('<p>')
        ->toContain('<strong>mondo</strong>')
        ->toContain('<a href="https://example.com" title="Esempio">qui</a>');
});

test('strips a script tag and its content entirely, never leaving it inline', function (): void {
    $html = '<p>Testo</p><script>alert(document.cookie)</script>';

    $sanitized = TicketMessageSanitizer::sanitize($html);

    expect($sanitized)->not->toContain('<script')
        ->and($sanitized)->not->toContain('alert(document.cookie)');
});

test('strips a disallowed element but is not in the allowlist for event handler attributes', function (): void {
    $html = '<p onclick="alert(1)">Testo</p><img src="x" onerror="alert(1)">';

    $sanitized = TicketMessageSanitizer::sanitize($html);

    expect($sanitized)->not->toContain('onclick')
        ->and($sanitized)->not->toContain('onerror')
        ->and($sanitized)->not->toContain('<img');
});

test('drops a disallowed link scheme like javascript:', function (): void {
    $html = '<a href="javascript:alert(1)">click</a>';

    $sanitized = TicketMessageSanitizer::sanitize($html);

    expect($sanitized)->not->toContain('javascript:');
});

test('derives a plain text version from the already-sanitized html', function (): void {
    $sanitizedHtml = '<p>Ciao <strong>mondo</strong></p>';

    expect(TicketMessageSanitizer::toPlainText($sanitizedHtml))->toBe('Ciao mondo');
});
