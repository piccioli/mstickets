<?php

declare(strict_types=1);

use App\Domain\Ticketing\Enums\TicketLogEvent;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('ticket_logs table has the columns required by §6.2.1', function (): void {
    expect(Schema::hasColumns('ticket_logs', [
        'id', 'ticket_id', 'user_id', 'event', 'from_status', 'to_status', 'changes',
        'is_system', 'occurred_at', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

test('event and status columns are cast to their backed enum, changes is a JSON diff (not the field body)', function (): void {
    $ticket = Ticket::create(['title' => 'Ticket con log', 'status_changed_at' => now()]);

    $log = TicketLog::create([
        'ticket_id' => $ticket->id,
        'event' => TicketLogEvent::StatusChanged,
        'from_status' => TicketStatus::New,
        'to_status' => TicketStatus::Assigned,
        'changes' => ['status' => ['from' => 'new', 'to' => 'assigned']],
        'occurred_at' => now(),
    ]);

    $fresh = $log->fresh();

    expect($fresh->event)->toBe(TicketLogEvent::StatusChanged)
        ->and($fresh->from_status)->toBe(TicketStatus::New)
        ->and($fresh->to_status)->toBe(TicketStatus::Assigned)
        ->and($fresh->changes)->toBe(['status' => ['from' => 'new', 'to' => 'assigned']])
        ->and($fresh->is_system)->toBeFalse();
});

test('deleting the ticket cascades to its logs', function (): void {
    $ticket = Ticket::create(['title' => 'Ticket con log', 'status_changed_at' => now()]);
    $log = TicketLog::create([
        'ticket_id' => $ticket->id, 'event' => TicketLogEvent::Created, 'occurred_at' => now(),
    ]);

    $ticket->forceDelete();

    expect(TicketLog::find($log->id))->toBeNull();
});
