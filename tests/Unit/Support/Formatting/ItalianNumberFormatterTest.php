<?php

declare(strict_types=1);

use App\Support\Formatting\ItalianNumberFormatter;

test('formats integers with the thousands dot', function (int|float $value, string $expected): void {
    expect(ItalianNumberFormatter::integer($value))->toBe($expected);
})->with([
    [1248, '1.248'],
    [999, '999'],
    [1234567, '1.234.567'],
    [-1248, "\u{2212}1.248"],
    [1248.6, '1.249'],
]);

test('formats kilometers with one decimal and the decimal comma', function (int|float $value, string $expected): void {
    expect(ItalianNumberFormatter::kilometers($value))->toBe($expected);
})->with([
    [412.63, '412,6 km'],
    [1412.6, '1.412,6 km'],
    [12, '12,0 km'],
]);

test('formats euros as rounded integers with the symbol after the amount', function (int|float $value, string $expected): void {
    expect(ItalianNumberFormatter::euro($value))->toBe($expected);
})->with([
    [186420.4, '186.420 €'],
    [186419.5, '186.420 €'],
    [-12340.0, "\u{2212}12.340 €"],
]);

test('formats a signed euro amount with the typographic minus', function (int|float $value, string $expected): void {
    expect(ItalianNumberFormatter::signedEuro($value))->toBe($expected);
})->with([
    [14110.0, '+14.110 €'],
    [-12340.0, "\u{2212}12.340 €"],
]);

test('shows zero as 0, never as missing and without a sign', function (): void {
    expect(ItalianNumberFormatter::integer(0))->toBe('0')
        ->and(ItalianNumberFormatter::kilometers(0))->toBe('0,0 km')
        ->and(ItalianNumberFormatter::euro(0.0))->toBe('0 €')
        ->and(ItalianNumberFormatter::signedEuro(0.0))->toBe('0 €')
        ->and(ItalianNumberFormatter::signedEuro(-0.4))->toBe('0 €')
        ->and(ItalianNumberFormatter::integer(-0.4))->toBe('0');
});
