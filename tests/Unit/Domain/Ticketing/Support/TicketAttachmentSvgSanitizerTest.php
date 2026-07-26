<?php

declare(strict_types=1);

use App\Domain\Ticketing\Support\TicketAttachmentSvgSanitizer;

test('strips a script element together with its content', function (): void {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><circle r="5"/></svg>';

    $sanitized = TicketAttachmentSvgSanitizer::sanitize($svg);

    expect($sanitized)->not->toContain('script')
        ->not->toContain('alert(1)')
        ->toContain('<circle');
});

test('strips on* event handler attributes', function (): void {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg"><rect onclick="alert(1)" width="10" height="10"/></svg>';

    $sanitized = TicketAttachmentSvgSanitizer::sanitize($svg);

    expect($sanitized)->not->toContain('onclick')
        ->not->toContain('alert(1)')
        ->toContain('width="10"');
});

test('strips a javascript: uri from an allowed attribute', function (): void {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg"><a xlink:href="javascript:alert(1)"><circle r="5"/></a></svg>';

    $sanitized = TicketAttachmentSvgSanitizer::sanitize($svg);

    expect($sanitized)->not->toContain('javascript:');
});

test('keeps an internal fragment href on a use element', function (): void {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">'.
        '<defs><circle id="c" r="5"/></defs><use xlink:href="#c"/></svg>';

    $sanitized = TicketAttachmentSvgSanitizer::sanitize($svg);

    expect($sanitized)->toContain('xlink:href="#c"');
});

test('strips an external href pointing outside the document', function (): void {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">'.
        '<use xlink:href="http://evil.example/x.svg#c"/></svg>';

    $sanitized = TicketAttachmentSvgSanitizer::sanitize($svg);

    expect($sanitized)->not->toContain('evil.example');
});

test('returns an empty svg shell for unparsable input instead of throwing', function (): void {
    $sanitized = TicketAttachmentSvgSanitizer::sanitize('not an svg at all <<<');

    expect($sanitized)->toContain('<svg');
});

test('removes a foreignObject element entirely', function (): void {
    $svg = '<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><body xmlns="http://www.w3.org/1999/xhtml">'.
        '<script>alert(1)</script></body></foreignObject></svg>';

    $sanitized = TicketAttachmentSvgSanitizer::sanitize($svg);

    expect($sanitized)->not->toContain('foreignObject')
        ->not->toContain('alert(1)');
});
