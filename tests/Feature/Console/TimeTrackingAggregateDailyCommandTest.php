<?php

declare(strict_types=1);

use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\TicketWorkLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->travelTo('2026-04-06 23:30:00'); // lunedì
});

test('consolidates a ticket with activity today, producing the same aggregates as timetracking:recalculate', function (): void {
    $user = userWithPermissions();

    $t1 = ticket();
    ticketLog($t1, ['to_status' => TicketStatus::Progress, 'occurred_at' => '2026-04-06 10:00:00', 'user_id' => $user->id]);
    ticketLog($t1, ['from_status' => TicketStatus::Progress, 'to_status' => TicketStatus::Todo, 'occurred_at' => '2026-04-06 11:00:00', 'user_id' => $user->id]);

    $t2 = ticket();
    ticketLog($t2, ['to_status' => TicketStatus::Progress, 'occurred_at' => '2026-04-06 10:00:00', 'user_id' => $user->id]);
    ticketLog($t2, ['from_status' => TicketStatus::Progress, 'to_status' => TicketStatus::Todo, 'occurred_at' => '2026-04-06 10:30:00', 'user_id' => $user->id]);

    $this->artisan('timetracking:aggregate-daily')->assertSuccessful();

    $aggregateWorkedMinutes = [$t1->fresh()->worked_minutes, $t2->fresh()->worked_minutes];
    $aggregateWorkLogMinutes = TicketWorkLog::query()->orderBy('ticket_id')->pluck('minutes')->all();

    $t1->fresh()->forceFill(['worked_minutes' => 0])->save();
    $t2->fresh()->forceFill(['worked_minutes' => 0])->save();
    TicketWorkLog::query()->delete();

    $this->artisan('timetracking:recalculate')->assertSuccessful();

    expect([$t1->fresh()->worked_minutes, $t2->fresh()->worked_minutes])->toBe($aggregateWorkedMinutes)
        ->and(TicketWorkLog::query()->orderBy('ticket_id')->pluck('minutes')->all())->toBe($aggregateWorkLogMinutes)
        ->and($t1->fresh()->worked_minutes)->toBe(60)
        ->and($t2->fresh()->worked_minutes)->toBe(30);
});

test('ignores a ticket without any activity today', function (): void {
    $user = userWithPermissions();

    $yesterday = ticket(['worked_minutes' => 999]);
    ticketLog($yesterday, ['to_status' => TicketStatus::Progress, 'occurred_at' => '2026-04-05 10:00:00', 'user_id' => $user->id]);
    ticketLog($yesterday, ['from_status' => TicketStatus::Progress, 'to_status' => TicketStatus::Todo, 'occurred_at' => '2026-04-05 11:00:00', 'user_id' => $user->id]);

    $this->artisan('timetracking:aggregate-daily')->assertSuccessful();

    expect($yesterday->fresh()->worked_minutes)->toBe(999)
        ->and(TicketWorkLog::query()->where('ticket_id', $yesterday->id)->count())->toBe(0);
});

test('--dry-run examines tickets with activity today without writing anything', function (): void {
    $user = userWithPermissions();

    $ticket = ticket();
    ticketLog($ticket, ['to_status' => TicketStatus::Progress, 'occurred_at' => '2026-04-06 10:00:00', 'user_id' => $user->id]);
    ticketLog($ticket, ['from_status' => TicketStatus::Progress, 'to_status' => TicketStatus::Todo, 'occurred_at' => '2026-04-06 11:00:00', 'user_id' => $user->id]);

    $this->artisan('timetracking:aggregate-daily', ['--dry-run' => true])->assertSuccessful();

    expect($ticket->fresh()->worked_minutes)->toBe(0);
    expect(TicketWorkLog::count())->toBe(0);
});

test('running it twice on the same day does not duplicate ticket_work_logs rows', function (): void {
    $user = userWithPermissions();

    $ticket = ticket();
    ticketLog($ticket, ['to_status' => TicketStatus::Progress, 'occurred_at' => '2026-04-06 10:00:00', 'user_id' => $user->id]);
    ticketLog($ticket, ['from_status' => TicketStatus::Progress, 'to_status' => TicketStatus::Todo, 'occurred_at' => '2026-04-06 11:00:00', 'user_id' => $user->id]);

    $this->artisan('timetracking:aggregate-daily')->assertSuccessful();
    $this->artisan('timetracking:aggregate-daily')->assertSuccessful();

    expect($ticket->fresh()->worked_minutes)->toBe(60)
        ->and(TicketWorkLog::query()->where('ticket_id', $ticket->id)->count())->toBe(1);
});
