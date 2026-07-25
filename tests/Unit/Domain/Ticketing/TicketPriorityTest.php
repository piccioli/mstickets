<?php

declare(strict_types=1);

use App\Domain\Ticketing\Enums\TicketPriority;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

test('contains exactly the 3 values (v1 numeric priorities normalized)', function (): void {
    expect(array_map(fn (TicketPriority $priority): string => $priority->value, TicketPriority::cases()))
        ->toBe(['low', 'medium', 'high']);
});

test('implements the Filament label/color/icon contracts', function (): void {
    expect(TicketPriority::Low)
        ->toBeInstanceOf(HasLabel::class)
        ->toBeInstanceOf(HasColor::class)
        ->toBeInstanceOf(HasIcon::class);
});

test('every case has a non-empty label, color and icon', function (): void {
    foreach (TicketPriority::cases() as $priority) {
        expect($priority->getLabel())->not->toBeEmpty();
        expect($priority->getColor())->not->toBeEmpty();
        expect($priority->getIcon())->not->toBeEmpty();
    }
});
