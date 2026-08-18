<?php

declare(strict_types=1);

use App\Domain\Ticketing\Support\WorkingDaysCalculator;
use Carbon\CarbonImmutable;

// Settimana di riferimento: lunedì 2026-08-10, venerdì 2026-08-14,
// weekend 15-16, lunedì successivo 2026-08-17, martedì 2026-08-18.
test('returns false when fewer working days than the threshold have fully elapsed', function (): void {
    $since = CarbonImmutable::parse('2026-08-10 10:00:00'); // Monday
    $asOf = CarbonImmutable::parse('2026-08-12 10:00:00'); // Wednesday, only 2 full weekdays elapsed

    expect(WorkingDaysCalculator::haveElapsed($since, 3, $asOf))->toBeFalse();
});

test('returns true once the threshold of full working days has elapsed', function (): void {
    $since = CarbonImmutable::parse('2026-08-10 10:00:00'); // Monday
    $asOf = CarbonImmutable::parse('2026-08-13 10:00:00'); // Thursday, Tue+Wed+Thu = 3 full weekdays

    expect(WorkingDaysCalculator::haveElapsed($since, 3, $asOf))->toBeTrue();
});

test('excludes Saturday and Sunday from the count', function (): void {
    $since = CarbonImmutable::parse('2026-08-14 10:00:00'); // Friday
    $notYet = CarbonImmutable::parse('2026-08-18 10:00:00'); // Tuesday: only Mon+Tue = 2 weekdays (weekend skipped)
    $elapsed = CarbonImmutable::parse('2026-08-19 10:00:00'); // Wednesday: Mon+Tue+Wed = 3 weekdays

    expect(WorkingDaysCalculator::haveElapsed($since, 3, $notYet))->toBeFalse()
        ->and(WorkingDaysCalculator::haveElapsed($since, 3, $elapsed))->toBeTrue();
});

test('never counts the day of the activity itself', function (): void {
    $since = CarbonImmutable::parse('2026-08-10 08:00:00'); // Monday
    $sameDayLater = CarbonImmutable::parse('2026-08-10 23:00:00');

    expect(WorkingDaysCalculator::haveElapsed($since, 1, $sameDayLater))->toBeFalse();
});

test('defaults to now() when asOf is omitted', function (): void {
    $farInThePast = CarbonImmutable::parse('2000-01-03 10:00:00'); // Monday

    expect(WorkingDaysCalculator::haveElapsed($farInThePast, 3))->toBeTrue();
});
