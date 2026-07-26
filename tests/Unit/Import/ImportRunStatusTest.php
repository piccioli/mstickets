<?php

declare(strict_types=1);

use App\Import\Enums\ImportRunStatus;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

test('contains exactly the 3 values required by §5.2', function (): void {
    expect(array_map(fn (ImportRunStatus $status): string => $status->value, ImportRunStatus::cases()))
        ->toBe(['running', 'completed', 'failed']);
});

test('every case has a non-empty label and a color', function (): void {
    foreach (ImportRunStatus::cases() as $status) {
        expect($status)->toBeInstanceOf(HasLabel::class)
            ->and($status)->toBeInstanceOf(HasColor::class);
        expect($status->getLabel())->not->toBeEmpty();
        expect($status->getColor())->not->toBeEmpty();
    }
});
