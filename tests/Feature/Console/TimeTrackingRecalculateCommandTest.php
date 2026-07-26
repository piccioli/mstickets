<?php

declare(strict_types=1);

use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\TicketWorkLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('recalculates every ticket when no option is given', function (): void {
    $user = userWithPermissions();

    $t1 = ticket();
    ticketLog($t1, ['to_status' => TicketStatus::Progress, 'occurred_at' => '2026-01-05 10:00:00', 'user_id' => $user->id]);
    ticketLog($t1, ['from_status' => TicketStatus::Progress, 'to_status' => TicketStatus::Todo, 'occurred_at' => '2026-01-05 11:00:00', 'user_id' => $user->id]);

    $t2 = ticket();
    ticketLog($t2, ['to_status' => TicketStatus::Progress, 'occurred_at' => '2026-01-06 10:00:00', 'user_id' => $user->id]);
    ticketLog($t2, ['from_status' => TicketStatus::Progress, 'to_status' => TicketStatus::Todo, 'occurred_at' => '2026-01-06 10:30:00', 'user_id' => $user->id]);

    $this->artisan('timetracking:recalculate')->assertSuccessful();

    expect($t1->fresh()->worked_minutes)->toBe(60)
        ->and($t2->fresh()->worked_minutes)->toBe(30);
});

test('--ticket limits the recalculation to a single ticket', function (): void {
    $user = userWithPermissions();

    $t1 = ticket();
    ticketLog($t1, ['to_status' => TicketStatus::Progress, 'occurred_at' => '2026-01-05 10:00:00', 'user_id' => $user->id]);
    ticketLog($t1, ['from_status' => TicketStatus::Progress, 'to_status' => TicketStatus::Todo, 'occurred_at' => '2026-01-05 11:00:00', 'user_id' => $user->id]);

    $t2 = ticket(['worked_minutes' => 999]);

    $this->artisan('timetracking:recalculate', ['--ticket' => $t1->id])->assertSuccessful();

    expect($t1->fresh()->worked_minutes)->toBe(60)
        ->and($t2->fresh()->worked_minutes)->toBe(999);
});

test('--from/--to filter tickets by created_at', function (): void {
    $user = userWithPermissions();

    $inRange = ticket();
    $inRange->forceFill(['created_at' => '2026-02-10 00:00:00'])->save();
    ticketLog($inRange, ['to_status' => TicketStatus::Progress, 'occurred_at' => '2026-02-10 10:00:00', 'user_id' => $user->id]);
    ticketLog($inRange, ['from_status' => TicketStatus::Progress, 'to_status' => TicketStatus::Todo, 'occurred_at' => '2026-02-10 10:20:00', 'user_id' => $user->id]);

    $outOfRange = ticket(['worked_minutes' => 999]);
    $outOfRange->forceFill(['created_at' => '2026-05-01 00:00:00'])->save();
    ticketLog($outOfRange, ['to_status' => TicketStatus::Progress, 'occurred_at' => '2026-05-01 10:00:00', 'user_id' => $user->id]);
    ticketLog($outOfRange, ['from_status' => TicketStatus::Progress, 'to_status' => TicketStatus::Todo, 'occurred_at' => '2026-05-01 10:20:00', 'user_id' => $user->id]);

    $this->artisan('timetracking:recalculate', ['--from' => '2026-02-01', '--to' => '2026-02-28'])->assertSuccessful();

    expect($inRange->fresh()->worked_minutes)->toBe(20)
        ->and($outOfRange->fresh()->worked_minutes)->toBe(999);
});

test('running it twice is idempotent', function (): void {
    $user = userWithPermissions();

    $t = ticket();
    ticketLog($t, ['to_status' => TicketStatus::Progress, 'occurred_at' => '2026-01-05 10:00:00', 'user_id' => $user->id]);
    ticketLog($t, ['from_status' => TicketStatus::Progress, 'to_status' => TicketStatus::Todo, 'occurred_at' => '2026-01-05 11:00:00', 'user_id' => $user->id]);

    $this->artisan('timetracking:recalculate')->assertSuccessful();
    $this->artisan('timetracking:recalculate')->assertSuccessful();

    expect($t->fresh()->worked_minutes)->toBe(60)
        ->and(TicketWorkLog::query()->where('ticket_id', $t->id)->count())->toBe(1);
});
