<?php

declare(strict_types=1);

use App\Domain\Mail\Parsers\QuotedTextRemover;

test('rimuove una citazione introdotta da "On ... wrote:"', function (): void {
    $text = <<<'TEXT'
    Confermo che il problema persiste.

    On Wed, Feb 4, 2026 at 9:00 AM Supporto <supporto@example.test> wrote:
    > Grazie per la segnalazione.
    TEXT;

    expect(QuotedTextRemover::strip($text))->toBe('Confermo che il problema persiste.');
});

test('non rimuove nulla quando non c\'è "wrote:" nel testo', function (): void {
    $text = 'Ho scritto questo messaggio senza citazioni.';

    expect(QuotedTextRemover::strip($text))->toBe($text);
});

test('rimuove una citazione introdotta da "Il ... ha scritto:"', function (): void {
    $text = <<<'TEXT'
    Confermo il problema.

    Il giorno 04/02/2026, Supporto <supporto@example.test> ha scritto:
    > Grazie per la segnalazione.
    TEXT;

    expect(QuotedTextRemover::strip($text))->toBe('Confermo il problema.');
});

test('rimuove righe citate che iniziano con >', function (): void {
    $text = "Testo nuovo.\n> riga citata 1\n> riga citata 2";

    expect(QuotedTextRemover::strip($text))->toBe('Testo nuovo.');
});

test('rimuove il blocco -----Original Message-----', function (): void {
    $text = "Testo nuovo.\n\n-----Original Message-----\nDa vecchio messaggio";

    expect(QuotedTextRemover::strip($text))->toBe('Testo nuovo.');
});

test('rimuove la firma introdotta dal separatore -- ', function (): void {
    $text = "Testo nuovo.\n\n--\nMario Rossi\nUfficio Tecnico";

    expect(QuotedTextRemover::strip($text))->toBe('Testo nuovo.');
});

test('rimuove un blocco header Outlook From:/Sent:/To:/Subject:', function (): void {
    $text = <<<'TEXT'
    Confermo.

    From: Supporto <supporto@example.test>
    Sent: Wednesday, February 4, 2026 9:00 AM
    To: Mario Rossi <cliente@example.test>
    Subject: Richiesta assistenza

    Grazie per la segnalazione.
    TEXT;

    expect(QuotedTextRemover::strip($text))->toBe('Confermo.');
});

test('rimuove un blocco header italiano Da:/Inviato:/A:/Oggetto:', function (): void {
    $text = <<<'TEXT'
    Confermo.

    Da: Supporto <supporto@example.test>
    Inviato: mercoledì 4 febbraio 2026 09:00
    A: Mario Rossi <cliente@example.test>
    Oggetto: Richiesta assistenza

    Grazie per la segnalazione.
    TEXT;

    expect(QuotedTextRemover::strip($text))->toBe('Confermo.');
});

test('non tratta come blocco Outlook una normale frase che inizia per From:', function (): void {
    $text = 'From: qui in poi lavoriamo diversamente, senza altri header sotto.';

    expect(QuotedTextRemover::strip($text))->toBe($text);
});

test('stripHtml rimuove i blockquote mantenendo il contenuto non citato', function (): void {
    $html = '<p>Testo nuovo.</p><blockquote><p>Testo citato.</p></blockquote>';

    $result = QuotedTextRemover::stripHtml($html);

    expect($result)->toContain('Testo nuovo.')
        ->and($result)->not->toContain('Testo citato.');
});
