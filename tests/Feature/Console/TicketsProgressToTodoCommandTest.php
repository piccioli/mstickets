<?php

declare(strict_types=1);

use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\TicketLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('--dry-run examines progress tickets without transitioning any of them', function (): void {
    $ticket = ticket(['status' => TicketStatus::Progress]);

    $this->artisan('tickets:progress-to-todo', ['--dry-run' => true])->assertSuccessful();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Progress);
    expect(TicketLog::count())->toBe(0);
});

test('transitions every progress ticket to todo via the state machine and logs it as a system action', function (): void {
    $ticket = ticket(['status' => TicketStatus::Progress]);

    $this->artisan('tickets:progress-to-todo')->assertSuccessful();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Todo);

    $log = TicketLog::query()->where('ticket_id', $ticket->id)->sole();
    expect($log->is_system)->toBeTrue()
        ->and($log->from_status)->toBe(TicketStatus::Progress)
        ->and($log->to_status)->toBe(TicketStatus::Todo);
});

test('does not touch tickets in a status other than progress', function (): void {
    $todo = ticket(['status' => TicketStatus::Todo]);
    $waiting = ticket(['status' => TicketStatus::Waiting, 'previous_status' => TicketStatus::Todo, 'waiting_reason' => 'In attesa di risposta']);

    $this->artisan('tickets:progress-to-todo')->assertSuccessful();

    expect($todo->fresh()->status)->toBe(TicketStatus::Todo)
        ->and($waiting->fresh()->status)->toBe(TicketStatus::Waiting);
});

test('re-running the command is idempotent: a ticket already todo is not transitioned again', function (): void {
    $ticket = ticket(['status' => TicketStatus::Progress]);

    $this->artisan('tickets:progress-to-todo')->assertSuccessful();
    $this->artisan('tickets:progress-to-todo')->assertSuccessful();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Todo);
    expect(TicketLog::query()->where('ticket_id', $ticket->id)->count())->toBe(1);
});
