<?php

declare(strict_types=1);

use App\Domain\Fundraising\Enums\FundraisingProjectStatus;
use Filament\Support\Contracts\HasLabel;

test('contains exactly the 5 values of §5.2', function (): void {
    expect(array_map(fn (FundraisingProjectStatus $status): string => $status->value, FundraisingProjectStatus::cases()))
        ->toBe(['draft', 'submitted', 'approved', 'rejected', 'completed']);
});

test('every case has a non-empty label', function (): void {
    foreach (FundraisingProjectStatus::cases() as $status) {
        expect($status)->toBeInstanceOf(HasLabel::class);
        expect($status->getLabel())->not->toBeEmpty();
    }
});
