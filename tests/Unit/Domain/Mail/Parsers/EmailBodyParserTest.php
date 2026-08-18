<?php

declare(strict_types=1);

use App\Domain\Mail\Parsers\EmailBodyParser;

test('preferisce il text/plain quando entrambi i corpi sono presenti', function (): void {
    $result = EmailBodyParser::parse(
        textPlain: 'Testo semplice.',
        textHtml: '<p>Testo <b>html</b>.</p>',
    );

    expect($result->bodyText)->toBe('Testo semplice.')
        ->and($result->bodyHtml)->toBe('<p>Testo <b>html</b>.</p>');
});

test('deriva il testo dall\'HTML quando il plain manca', function (): void {
    $result = EmailBodyParser::parse(
        textPlain: null,
        textHtml: '<p>Solo HTML disponibile.</p>',
    );

    expect($result->bodyText)->toBe('Solo HTML disponibile.')
        ->and($result->bodyHtml)->toBe('<p>Solo HTML disponibile.</p>');
});

test('sanitizza sempre il body_html rimuovendo tag non in allowlist', function (): void {
    $result = EmailBodyParser::parse(
        textPlain: 'Testo.',
        textHtml: '<p>Testo</p><script>alert(1)</script>',
    );

    expect($result->bodyHtml)->toBe('<p>Testo</p>')
        ->and($result->bodyHtml)->not->toContain('script');
});

test('rimuove il testo citato sia dal testo che dall\'html derivato', function (): void {
    $result = EmailBodyParser::parse(
        textPlain: null,
        textHtml: '<p>Nuovo contenuto.</p><blockquote><p>Contenuto citato.</p></blockquote>',
    );

    expect($result->bodyHtml)->not->toContain('Contenuto citato')
        ->and($result->bodyText)->toBe('Nuovo contenuto.');
});

test('restituisce entrambi i campi null quando non c\'è alcun corpo utile', function (): void {
    $result = EmailBodyParser::parse(textPlain: null, textHtml: null);

    expect($result->bodyText)->toBeNull()
        ->and($result->bodyHtml)->toBeNull();
});

test('un plain fatto solo di spazi cade comunque sull\'HTML', function (): void {
    $result = EmailBodyParser::parse(
        textPlain: "   \n  ",
        textHtml: '<p>Contenuto reale.</p>',
    );

    expect($result->bodyText)->toBe('Contenuto reale.');
});
