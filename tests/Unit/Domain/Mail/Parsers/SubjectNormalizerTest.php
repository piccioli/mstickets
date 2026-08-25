<?php

declare(strict_types=1);

use App\Domain\Mail\Parsers\SubjectNormalizer;

test('rimuove un singolo prefisso di risposta', function (string $prefix): void {
    $result = SubjectNormalizer::normalize("{$prefix}: Problema stampante");

    expect($result->subject)->toBe('Problema stampante')
        ->and($result->ticketId)->toBeNull();
})->with(['Re', 'RE', 'R', 'Fw', 'Fwd', 'AW', 'I', 'Rif']);

test('rimuove prefissi in cascata', function (): void {
    $result = SubjectNormalizer::normalize('Fwd: Re: RE: Problema stampante');

    expect($result->subject)->toBe('Problema stampante');
});

test('lascia invariato un subject senza prefissi', function (): void {
    $result = SubjectNormalizer::normalize('Problema stampante');

    expect($result->subject)->toBe('Problema stampante');
});

test('estrae il token [#<id>] senza rimuoverlo dal subject normalizzato', function (): void {
    $result = SubjectNormalizer::normalize('Re: [#42] Richiesta assistenza');

    expect($result->ticketId)->toBe(42)
        ->and($result->subject)->toBe('[#42] Richiesta assistenza');
});

test('un subject senza token restituisce ticketId null', function (): void {
    $result = SubjectNormalizer::normalize('Richiesta assistenza');

    expect($result->ticketId)->toBeNull();
});

test('decodifica un subject con encoded-word RFC 2047', function (): void {
    $result = SubjectNormalizer::normalize('=?UTF-8?Q?Problema_con_caff=C3=A8?=');

    expect($result->subject)->toBe('Problema con caffè');
});

test('un subject null diventa una stringa vuota senza errori', function (): void {
    $result = SubjectNormalizer::normalize(null);

    expect($result->subject)->toBe('')
        ->and($result->ticketId)->toBeNull();
});

test('normalizeForThreadMatching rimuove prefissi, token [#id], collassa spazi e minuscolo', function (): void {
    expect(SubjectNormalizer::normalizeForThreadMatching('Re: [#42]   Problema  di Accesso'))
        ->toBe('problema di accesso');
});

test('normalizeForThreadMatching produce la stessa chiave usata da DeriveStage per un titolo senza prefissi', function (): void {
    expect(SubjectNormalizer::normalizeForThreadMatching('Problema di accesso'))
        ->toBe('problema di accesso');
});

test('normalizeForThreadMatching su null restituisce una stringa vuota', function (): void {
    expect(SubjectNormalizer::normalizeForThreadMatching(null))->toBe('');
});
