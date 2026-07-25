<?php

declare(strict_types=1);

use App\Domain\Ticketing\Enums\TicketStatus;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

test('contains exactly the 12 values of the v1, case Testing (not Test)', function (): void {
    expect(array_map(fn (TicketStatus $status): string => $status->value, TicketStatus::cases()))
        ->toBe([
            'new', 'backlog', 'assigned', 'todo', 'progress', 'testing', 'tested',
            'released', 'done', 'problem', 'waiting', 'rejected',
        ])
        ->and(TicketStatus::Testing->name)->toBe('Testing');
});

test('implements the Filament label/color/icon contracts', function (): void {
    expect(TicketStatus::New)
        ->toBeInstanceOf(HasLabel::class)
        ->toBeInstanceOf(HasColor::class)
        ->toBeInstanceOf(HasIcon::class);
});

test('every case has a non-empty label, color and icon', function (): void {
    foreach (TicketStatus::cases() as $status) {
        expect($status->getLabel())->not->toBeEmpty();
        expect($status->getColor())->not->toBeEmpty();
        expect($status->getIcon())->not->toBeEmpty();
    }
});
