<?php

declare(strict_types=1);

use App\Domain\Mail\Enums\EmailDirection;
use Filament\Support\Contracts\HasLabel;

test('contains exactly inbound and outbound', function (): void {
    expect(array_map(fn (EmailDirection $direction): string => $direction->value, EmailDirection::cases()))
        ->toBe(['inbound', 'outbound']);
});

test('implements the Filament label contract with a non-empty label per case', function (): void {
    foreach (EmailDirection::cases() as $direction) {
        expect($direction)->toBeInstanceOf(HasLabel::class);
        expect($direction->getLabel())->not->toBeEmpty();
    }
});
