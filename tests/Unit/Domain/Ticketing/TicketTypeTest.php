<?php

declare(strict_types=1);

use App\Domain\Ticketing\Enums\TicketType;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

test('contains exactly the 4 normalized v2 values', function (): void {
    expect(array_map(fn (TicketType $type): string => $type->value, TicketType::cases()))
        ->toBe(['bug', 'feature', 'helpdesk', 'scrum']);
});

test('implements the Filament label/color/icon contracts', function (): void {
    expect(TicketType::Bug)
        ->toBeInstanceOf(HasLabel::class)
        ->toBeInstanceOf(HasColor::class)
        ->toBeInstanceOf(HasIcon::class);
});

test('every case has a non-empty label, color and icon', function (): void {
    foreach (TicketType::cases() as $type) {
        expect($type->getLabel())->not->toBeEmpty();
        expect($type->getColor())->not->toBeEmpty();
        expect($type->getIcon())->not->toBeEmpty();
    }
});
