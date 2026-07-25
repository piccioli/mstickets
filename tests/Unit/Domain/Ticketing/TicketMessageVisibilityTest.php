<?php

declare(strict_types=1);

use App\Domain\Ticketing\Enums\TicketMessageVisibility;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

test('contains exactly the 2 values of §5.2 (only Public is exposed in the UI this release)', function (): void {
    expect(array_map(fn (TicketMessageVisibility $visibility): string => $visibility->value, TicketMessageVisibility::cases()))
        ->toBe(['public', 'internal']);
});

test('every case has a non-empty label and color', function (): void {
    foreach (TicketMessageVisibility::cases() as $visibility) {
        expect($visibility)->toBeInstanceOf(HasLabel::class)->toBeInstanceOf(HasColor::class);
        expect($visibility->getLabel())->not->toBeEmpty();
        expect($visibility->getColor())->not->toBeEmpty();
    }
});
