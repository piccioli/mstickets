<?php

declare(strict_types=1);

use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\TicketWorkLog;
use App\Domain\TimeTracking\Actions\RecalculateWorkedTime;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('recalculates tickets.worked_minutes and ticket_work_logs from ticket_logs', function (): void {
    $user = userWithPermissions();
    $t = ticket();

    ticketLog($t, ['to_status' => TicketStatus::Progress, 'occurred_at' => '2026-01-05 10:00:00', 'user_id' => $user->id]);
    ticketLog($t, ['from_status' => TicketStatus::Progress, 'to_status' => TicketStatus::Todo, 'occurred_at' => '2026-01-05 12:00:00', 'user_id' => $user->id]);

    RecalculateWorkedTime::run($t);

    expect($t->fresh()->worked_minutes)->toBe(120);

    $workLog = TicketWorkLog::query()->where('ticket_id', $t->id)->sole();

    expect($workLog->work_date->toDateString())->toBe('2026-01-05')
        ->and($workLog->user_id)->toBe($user->id)
        ->and($workLog->minutes)->toBe(120);
});

test('is idempotent: running it twice does not duplicate ticket_work_logs rows', function (): void {
    $user = userWithPermissions();
    $t = ticket();

    ticketLog($t, ['to_status' => TicketStatus::Progress, 'occurred_at' => '2026-01-05 10:00:00', 'user_id' => $user->id]);
    ticketLog($t, ['from_status' => TicketStatus::Progress, 'to_status' => TicketStatus::Todo, 'occurred_at' => '2026-01-05 12:00:00', 'user_id' => $user->id]);

    RecalculateWorkedTime::run($t);
    RecalculateWorkedTime::run($t);

    expect(TicketWorkLog::query()->where('ticket_id', $t->id)->count())->toBe(1)
        ->and($t->fresh()->worked_minutes)->toBe(120);
});

test('recalculating removes stale ticket_work_logs rows that no longer apply', function (): void {
    $user = userWithPermissions();
    $t = ticket();

    ticketLog($t, ['to_status' => TicketStatus::Progress, 'occurred_at' => '2026-01-05 10:00:00', 'user_id' => $user->id]);
    ticketLog($t, ['from_status' => TicketStatus::Progress, 'to_status' => TicketStatus::Todo, 'occurred_at' => '2026-01-05 12:00:00', 'user_id' => $user->id]);

    RecalculateWorkedTime::run($t);

    expect(TicketWorkLog::query()->where('ticket_id', $t->id)->count())->toBe(1);

    // A ticket with no progress interval at all recalculates to zero rows/minutes.
    $t->logs()->delete();

    RecalculateWorkedTime::run($t);

    expect(TicketWorkLog::query()->where('ticket_id', $t->id)->count())->toBe(0)
        ->and($t->fresh()->worked_minutes)->toBe(0);
});

test('aggregates minutes per user across multiple assignees over time', function (): void {
    $developer1 = userWithPermissions();
    $developer2 = userWithPermissions();
    $t = ticket();

    ticketLog($t, ['to_status' => TicketStatus::Progress, 'occurred_at' => '2026-01-05 10:00:00', 'user_id' => $developer1->id]);
    ticketLog($t, ['from_status' => TicketStatus::Progress, 'to_status' => TicketStatus::Todo, 'occurred_at' => '2026-01-05 11:00:00', 'user_id' => $developer1->id]);
    ticketLog($t, ['to_status' => TicketStatus::Progress, 'occurred_at' => '2026-01-06 10:00:00', 'user_id' => $developer2->id]);
    ticketLog($t, ['from_status' => TicketStatus::Progress, 'to_status' => TicketStatus::Done, 'occurred_at' => '2026-01-06 10:20:00', 'user_id' => $developer2->id]);

    RecalculateWorkedTime::run($t);

    expect($t->fresh()->worked_minutes)->toBe(80);
    expect(TicketWorkLog::query()->where('ticket_id', $t->id)->where('user_id', $developer1->id)->sole()->minutes)->toBe(60);
    expect(TicketWorkLog::query()->where('ticket_id', $t->id)->where('user_id', $developer2->id)->sole()->minutes)->toBe(20);
});
