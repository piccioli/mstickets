<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Support\CaiRichTextSanitizer;

test('strips inline styling but keeps the text content of a span', function (): void {
    $html = 'Martedi dalle 18 alle 19&nbsp;<span style="background-color: rgb(254, 251, 243);">(da ottobre a maggio, restanti mesi chiuso)</span><br>Venerdi dalle 21 alle 22:30';

    $sanitized = CaiRichTextSanitizer::sanitize($html);

    expect($sanitized)
        ->toContain('Martedi dalle 18 alle 19')
        ->toContain('(da ottobre a maggio, restanti mesi chiuso)')
        ->toContain('Venerdi dalle 21 alle 22:30')
        ->not->toContain('style=')
        ->not->toContain('background-color');
});

test('strips a script tag and its content entirely', function (): void {
    $html = '<script>alert(document.cookie)</script><p>Sede chiusa per lavori.</p>';

    $sanitized = CaiRichTextSanitizer::sanitize($html);

    expect($sanitized)->not->toContain('<script')
        ->and($sanitized)->not->toContain('alert(document.cookie)')
        ->and($sanitized)->toContain('Sede chiusa per lavori.');
});

test('strips event handler attributes', function (): void {
    $html = '<p onclick="alert(1)">Testo</p>';

    $sanitized = CaiRichTextSanitizer::sanitize($html);

    expect($sanitized)->not->toContain('onclick');
});
