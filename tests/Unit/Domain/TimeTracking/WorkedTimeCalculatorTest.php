<?php

declare(strict_types=1);

use App\Domain\Ticketing\Enums\TicketLogEvent;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\TicketLog;
use App\Domain\TimeTracking\WorkedTimeCalculator;
use Carbon\CarbonImmutable;

/**
 * Costruisce un `ticket_log` in memoria (mai salvato: il calcolatore è puro e non
 * tocca il DB), con solo gli attributi rilevanti per l'algoritmo.
 */
function fakeTicketLog(?TicketStatus $from, ?TicketStatus $to, string $occurredAt, int $userId = 1): TicketLog
{
    return new TicketLog([
        'event' => TicketLogEvent::StatusChanged,
        'from_status' => $from,
        'to_status' => $to,
        'occurred_at' => $occurredAt,
        'user_id' => $userId,
    ]);
}

beforeEach(function (): void {
    $this->calculator = new WorkedTimeCalculator(
        workdayStart: 9,
        workdayEnd: 18,
        granularityMinutes: 10,
        nonStatusChangeCapMinutes: 30,
    );
});

test('computes minutes for a closed interval within a single day window', function (): void {
    $logs = [
        fakeTicketLog(TicketStatus::Todo, TicketStatus::Progress, '2026-01-05 10:00:00'),
        fakeTicketLog(TicketStatus::Progress, TicketStatus::Todo, '2026-01-05 12:00:00'),
    ];

    expect($this->calculator->totalMinutesFor($logs))->toBe(120);
});

test('rounds down to the configured granularity', function (): void {
    $logs = [
        fakeTicketLog(TicketStatus::Todo, TicketStatus::Progress, '2026-01-05 10:00:00'),
        fakeTicketLog(TicketStatus::Progress, TicketStatus::Todo, '2026-01-05 10:37:00'),
    ];

    expect($this->calculator->totalMinutesFor($logs))->toBe(30);
});

test('excludes the weekend and clamps to the workday window', function (): void {
    // Monday 2026-01-05, Friday before it is 2026-01-02.
    $logs = [
        fakeTicketLog(TicketStatus::Todo, TicketStatus::Progress, '2026-01-02 16:00:00'), // Friday
        fakeTicketLog(TicketStatus::Progress, TicketStatus::Todo, '2026-01-05 10:00:00'), // Monday
    ];

    // Friday 16:00-18:00 = 120', Saturday/Sunday skipped, Monday 9:00-10:00 = 60'.
    expect($this->calculator->totalMinutesFor($logs))->toBe(180);
});

test('splits correctly across midnight', function (): void {
    $logs = [
        fakeTicketLog(TicketStatus::Todo, TicketStatus::Progress, '2026-01-05 17:30:00'), // Monday
        fakeTicketLog(TicketStatus::Progress, TicketStatus::Todo, '2026-01-06 09:20:00'), // Tuesday
    ];

    // Monday 17:30-18:00 = 30', Tuesday 9:00-9:20 = 20'.
    expect($this->calculator->totalMinutesFor($logs))->toBe(50);

    $segments = $this->calculator->segmentsFor($logs);

    expect($segments)->toHaveCount(2);
});

test('sums independent intervals when a ticket is reopened weeks later', function (): void {
    $logs = [
        fakeTicketLog(TicketStatus::Todo, TicketStatus::Progress, '2026-01-05 10:00:00'),
        fakeTicketLog(TicketStatus::Progress, TicketStatus::Todo, '2026-01-05 11:00:00'),
        fakeTicketLog(TicketStatus::Todo, TicketStatus::Progress, '2026-02-02 10:00:00'),
        fakeTicketLog(TicketStatus::Progress, TicketStatus::Done, '2026-02-02 10:30:00'),
    ];

    expect($this->calculator->totalMinutesFor($logs))->toBe(90);

    $segments = $this->calculator->segmentsFor($logs);

    expect($segments)->toHaveCount(2);
});

test('attributes each interval to the user who triggered it, across multiple assignees over time', function (): void {
    $logs = [
        fakeTicketLog(TicketStatus::Todo, TicketStatus::Progress, '2026-01-05 10:00:00', userId: 1),
        fakeTicketLog(TicketStatus::Progress, TicketStatus::Todo, '2026-01-05 11:00:00', userId: 1),
        fakeTicketLog(TicketStatus::Todo, TicketStatus::Progress, '2026-01-06 10:00:00', userId: 2),
        fakeTicketLog(TicketStatus::Progress, TicketStatus::Done, '2026-01-06 10:20:00', userId: 2),
    ];

    $segments = $this->calculator->segmentsFor($logs);

    expect($segments)->toHaveCount(2);

    $byUser = collect($segments)->keyBy('userId');

    expect($byUser[1]->minutes)->toBe(60)
        ->and($byUser[2]->minutes)->toBe(20);
});

test('caps a still-open interval instead of projecting it indefinitely', function (): void {
    $logs = [
        fakeTicketLog(TicketStatus::Todo, TicketStatus::Progress, '2026-01-05 10:00:00'),
    ];

    $asOf = CarbonImmutable::parse('2026-01-09 15:00:00'); // several workdays later, ticket still in progress

    $segments = $this->calculator->segmentsFor($logs, $asOf);

    expect($this->calculator->totalMinutesFor($logs, $asOf))->toBe(30)
        ->and($segments)->toHaveCount(1)
        ->and($segments[0]->workDate->toDateString())->toBe('2026-01-09');
});

test('does not cap an open interval that has not yet reached the cap', function (): void {
    $logs = [
        fakeTicketLog(TicketStatus::Todo, TicketStatus::Progress, '2026-01-05 10:00:00'),
    ];

    $asOf = CarbonImmutable::parse('2026-01-05 10:15:00');

    expect($this->calculator->totalMinutesFor($logs, $asOf))->toBe(10);
});

test('is idempotent: calling it twice with the same logs and reference instant yields the same result', function (): void {
    $logs = [
        fakeTicketLog(TicketStatus::Todo, TicketStatus::Progress, '2026-01-05 10:00:00'),
        fakeTicketLog(TicketStatus::Progress, TicketStatus::Todo, '2026-01-05 12:00:00'),
    ];

    $first = $this->calculator->totalMinutesFor($logs);
    $second = $this->calculator->totalMinutesFor($logs);

    expect($first)->toBe($second)->toBe(120);
});

test('ignores an unopened interval (from_status progress with no prior opening log)', function (): void {
    $logs = [
        fakeTicketLog(TicketStatus::Progress, TicketStatus::Todo, '2026-01-05 12:00:00'),
    ];

    expect($this->calculator->totalMinutesFor($logs))->toBe(0);
});
