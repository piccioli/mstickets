<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\UserRole;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

test('contains exactly the 5 roles of PRD §9.2, no editor', function (): void {
    expect(array_map(fn (UserRole $role): string => $role->value, UserRole::cases()))
        ->toBe(['admin', 'developer', 'manager', 'customer', 'fundraising']);
});

test('implements the Filament label/color/icon contracts', function (): void {
    expect(UserRole::Admin)
        ->toBeInstanceOf(HasLabel::class)
        ->toBeInstanceOf(HasColor::class)
        ->toBeInstanceOf(HasIcon::class);
});

test('every case has a non-empty label, color and icon', function (): void {
    foreach (UserRole::cases() as $role) {
        expect($role->getLabel())->not->toBeEmpty();
        expect($role->getColor())->not->toBeEmpty();
        expect($role->getIcon())->not->toBeEmpty();
    }
});
