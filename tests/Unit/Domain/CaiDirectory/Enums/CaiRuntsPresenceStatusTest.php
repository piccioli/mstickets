<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiRuntsPresenceStatus;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

test('every case has a label and a color', function (): void {
    foreach (CaiRuntsPresenceStatus::cases() as $status) {
        expect($status)->toBeInstanceOf(HasLabel::class)
            ->and($status)->toBeInstanceOf(HasColor::class)
            ->and($status->getLabel())->not->toBe('')
            ->and($status->getColor())->not->toBe('');
    }
});

test('the three expected cases exist with the expected string values', function (): void {
    expect(CaiRuntsPresenceStatus::Registered->value)->toBe('registered');
    expect(CaiRuntsPresenceStatus::NotRegistered->value)->toBe('not_registered');
    expect(CaiRuntsPresenceStatus::Timeout->value)->toBe('timeout');
});
