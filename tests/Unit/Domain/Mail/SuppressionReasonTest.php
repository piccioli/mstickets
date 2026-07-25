<?php

declare(strict_types=1);

use App\Domain\Mail\Enums\SuppressionReason;
use Filament\Support\Contracts\HasLabel;

test('contains exactly the 5 values of the AC', function (): void {
    expect(array_map(fn (SuppressionReason $reason): string => $reason->value, SuppressionReason::cases()))
        ->toBe(['hard_bounce', 'soft_bounce', 'complaint', 'manual', 'loop_protection']);
});

test('implements the Filament label contract with a non-empty label per case', function (): void {
    foreach (SuppressionReason::cases() as $reason) {
        expect($reason)->toBeInstanceOf(HasLabel::class);
        expect($reason->getLabel())->not->toBeEmpty();
    }
});
