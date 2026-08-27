<?php

declare(strict_types=1);

use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\TicketLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Lunedì: 3 giorni lavorativi prima è mercoledì (nessun weekend nel mezzo),
    // stesso principio di calcolo di WorkingDaysCalculator già validato da
    // TicketsRemindWaitingCommandTest (US-316).
    $this->travelTo('2026-03-09 07:45:00');
});

test('--dry-run examines released tickets without closing any of them', function (): void {
    $ticket = ticket(['status' => TicketStatus::Released, 'released_at' => '2026-03-04 10:00:00']);

    $this->artisan('tickets:auto-close-released', ['--dry-run' => true])->assertSuccessful();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Released);
    expect(TicketLog::count())->toBe(0);
});

test('closes a ticket released for at least the configured working days threshold and stamps done_at', function (): void {
    $ticket = ticket(['status' => TicketStatus::Released, 'released_at' => '2026-03-04 10:00:00']);

    $this->artisan('tickets:auto-close-released')->assertSuccessful();

    $fresh = $ticket->fresh();
    expect($fresh->status)->toBe(TicketStatus::Done)
        ->and($fresh->done_at)->not->toBeNull();

    $log = TicketLog::query()->where('ticket_id', $ticket->id)->sole();
    expect($log->is_system)->toBeTrue()
        ->and($log->from_status)->toBe(TicketStatus::Released)
        ->and($log->to_status)->toBe(TicketStatus::Done);
});

test('does not close a ticket released more recently than the threshold', function (): void {
    $ticket = ticket(['status' => TicketStatus::Released, 'released_at' => '2026-03-06 10:00:00']);

    $this->artisan('tickets:auto-close-released')->assertSuccessful();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Released);
    expect(TicketLog::count())->toBe(0);
});

test('does not touch tickets in a status other than released', function (): void {
    $todo = ticket(['status' => TicketStatus::Todo]);

    $this->artisan('tickets:auto-close-released')->assertSuccessful();

    expect($todo->fresh()->status)->toBe(TicketStatus::Todo);
});

test('re-running the command is idempotent: a ticket already done is not transitioned again', function (): void {
    $ticket = ticket(['status' => TicketStatus::Released, 'released_at' => '2026-03-04 10:00:00']);

    $this->artisan('tickets:auto-close-released')->assertSuccessful();
    $this->artisan('tickets:auto-close-released')->assertSuccessful();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Done);
    expect(TicketLog::query()->where('ticket_id', $ticket->id)->count())->toBe(1);
});
