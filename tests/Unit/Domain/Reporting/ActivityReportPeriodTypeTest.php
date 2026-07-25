<?php

declare(strict_types=1);

use App\Domain\Reporting\Enums\ActivityReportPeriodType;
use Filament\Support\Contracts\HasLabel;

test('contains exactly the 2 values of §5.2', function (): void {
    expect(array_map(fn (ActivityReportPeriodType $type): string => $type->value, ActivityReportPeriodType::cases()))
        ->toBe(['monthly', 'annual']);
});

test('every case has a non-empty label', function (): void {
    foreach (ActivityReportPeriodType::cases() as $type) {
        expect($type)->toBeInstanceOf(HasLabel::class);
        expect($type->getLabel())->not->toBeEmpty();
    }
});
