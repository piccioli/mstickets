<?php

declare(strict_types=1);

use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\TicketLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->travelTo('2026-03-10 06:30:00');
});

test('--dry-run examines restorable waiting tickets without restoring any of them', function (): void {
    $ticket = ticket([
        'status' => TicketStatus::Waiting,
        'previous_status' => TicketStatus::Todo,
        'status_changed_at' => '2026-03-03 10:00:00',
    ]);

    $this->artisan('tickets:restore-waiting', ['--dry-run' => true])->assertSuccessful();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Waiting);
    expect(TicketLog::count())->toBe(0);
});

test('restores a ticket waiting for exactly the configured threshold of calendar days', function (): void {
    $ticket = ticket([
        'status' => TicketStatus::Waiting,
        'previous_status' => TicketStatus::Todo,
        'status_changed_at' => '2026-03-03 06:30:00', // exactly 7 calendar days before "now"
    ]);

    $this->artisan('tickets:restore-waiting')->assertSuccessful();

    $fresh = $ticket->fresh();
    expect($fresh->status)->toBe(TicketStatus::Todo)
        ->and($fresh->previous_status)->toBeNull();

    $log = TicketLog::query()->where('ticket_id', $ticket->id)->sole();
    expect($log->is_system)->toBeTrue()
        ->and($log->from_status)->toBe(TicketStatus::Waiting)
        ->and($log->to_status)->toBe(TicketStatus::Todo);
});

test('restores a ticket waiting for more than the configured threshold of calendar days', function (): void {
    $ticket = ticket([
        'status' => TicketStatus::Waiting,
        'previous_status' => TicketStatus::Progress,
        'status_changed_at' => '2026-03-02 10:00:00', // 8 calendar days ago
    ]);

    $this->artisan('tickets:restore-waiting')->assertSuccessful();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Progress);
});

test('does not restore a ticket waiting for one day less than the configured threshold', function (): void {
    $ticket = ticket([
        'status' => TicketStatus::Waiting,
        'previous_status' => TicketStatus::Todo,
        'status_changed_at' => '2026-03-04 10:00:00', // 6 calendar days ago
    ]);

    $this->artisan('tickets:restore-waiting')->assertSuccessful();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Waiting);
    expect(TicketLog::count())->toBe(0);
});

test('does not touch tickets in a status other than waiting', function (): void {
    $todo = ticket(['status' => TicketStatus::Todo]);

    $this->artisan('tickets:restore-waiting')->assertSuccessful();

    expect($todo->fresh()->status)->toBe(TicketStatus::Todo);
});

test('does not touch a waiting ticket without a previous status', function (): void {
    $ticket = ticket([
        'status' => TicketStatus::Waiting,
        'previous_status' => null,
        'status_changed_at' => '2026-03-01 10:00:00',
    ]);

    $this->artisan('tickets:restore-waiting')->assertSuccessful();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Waiting);
});

test('re-running the command is idempotent: a restored ticket is not touched again', function (): void {
    $ticket = ticket([
        'status' => TicketStatus::Waiting,
        'previous_status' => TicketStatus::Todo,
        'status_changed_at' => '2026-03-01 10:00:00',
    ]);

    $this->artisan('tickets:restore-waiting')->assertSuccessful();
    $this->artisan('tickets:restore-waiting')->assertSuccessful();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Todo);
    expect(TicketLog::query()->where('ticket_id', $ticket->id)->count())->toBe(1);
});
