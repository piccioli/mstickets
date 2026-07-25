<?php

declare(strict_types=1);

use App\Support\DesignTokens;

test('reads the brand color token from resources/css/theme.css', function (): void {
    expect(DesignTokens::get('ms-brand'))->toBe('#17a180');
});

test('resolves the primary font family without quotes', function (): void {
    expect(DesignTokens::primaryFontFamily())->toBe('Nunito Sans');
});

test('throws when a token does not exist', function (): void {
    DesignTokens::get('ms-does-not-exist');
})->throws(RuntimeException::class);
