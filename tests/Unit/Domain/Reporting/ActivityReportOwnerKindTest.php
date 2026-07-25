<?php

declare(strict_types=1);

use App\Domain\Reporting\Enums\ActivityReportOwnerKind;
use Filament\Support\Contracts\HasLabel;

test('contains exactly the 2 values of §5.2', function (): void {
    expect(array_map(fn (ActivityReportOwnerKind $kind): string => $kind->value, ActivityReportOwnerKind::cases()))
        ->toBe(['user', 'organization']);
});

test('every case has a non-empty label', function (): void {
    foreach (ActivityReportOwnerKind::cases() as $kind) {
        expect($kind)->toBeInstanceOf(HasLabel::class);
        expect($kind->getLabel())->not->toBeEmpty();
    }
});
