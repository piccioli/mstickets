<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketPriority;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Enums\TicketType;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('tickets table has the columns required by §5.2', function (): void {
    expect(Schema::hasColumns('tickets', [
        'id', 'parent_id', 'title', 'description', 'status', 'previous_status', 'status_changed_at',
        'type', 'priority', 'requester_id', 'assignee_id', 'tester_id', 'fundraising_project_id',
        'waiting_reason', 'problem_reason', 'estimated_hours', 'worked_minutes', 'staging_url',
        'production_url', 'released_at', 'done_at', 'created_at', 'updated_at', 'deleted_at',
    ]))->toBeTrue();
});

test('a ticket applies the documented defaults', function (): void {
    $ticket = Ticket::create(['title' => 'Errore login', 'status_changed_at' => now()])->fresh();

    expect($ticket->status)->toBe(TicketStatus::New)
        ->and($ticket->type)->toBe(TicketType::Helpdesk)
        ->and($ticket->priority)->toBe(TicketPriority::Low)
        ->and($ticket->worked_minutes)->toBe(0);
});

test('status/type/priority are cast to their backed enum', function (): void {
    $ticket = Ticket::create([
        'title' => 'Nuova feature',
        'status' => TicketStatus::Progress,
        'type' => TicketType::Feature,
        'priority' => TicketPriority::High,
        'status_changed_at' => now(),
    ]);

    expect($ticket->fresh()->status)->toBe(TicketStatus::Progress)
        ->and($ticket->fresh()->type)->toBe(TicketType::Feature)
        ->and($ticket->fresh()->priority)->toBe(TicketPriority::High);
});

test('a ticket can have a parent (depth handled by validation, not schema)', function (): void {
    $parent = Ticket::create(['title' => 'Epic', 'status_changed_at' => now()]);
    $child = Ticket::create(['title' => 'Sotto-ticket', 'parent_id' => $parent->id, 'status_changed_at' => now()]);

    expect($child->parent->id)->toBe($parent->id)
        ->and($parent->children()->count())->toBe(1);
});

test('deleting the parent sets parent_id to null on children', function (): void {
    $parent = Ticket::create(['title' => 'Epic', 'status_changed_at' => now()]);
    $child = Ticket::create(['title' => 'Sotto-ticket', 'parent_id' => $parent->id, 'status_changed_at' => now()]);

    $parent->forceDelete();

    expect($child->fresh()->parent_id)->toBeNull();
});

test('deleting requester/assignee/tester sets the FK to null, not cascading the ticket', function (): void {
    $requester = User::factory()->create();
    $ticket = Ticket::create([
        'title' => 'Richiesta cliente',
        'requester_id' => $requester->id,
        'status_changed_at' => now(),
    ]);

    $requester->forceDelete();

    expect($ticket->fresh())->not->toBeNull()
        ->and($ticket->fresh()->requester_id)->toBeNull();
});

test('a soft-deleted ticket is excluded from default queries but stays in the database', function (): void {
    $ticket = Ticket::create(['title' => 'Da archiviare', 'status_changed_at' => now()]);

    $ticket->delete();

    expect(Ticket::find($ticket->id))->toBeNull()
        ->and(Ticket::withTrashed()->find($ticket->id))->not->toBeNull();
});

test('the requester_id/status pair need not be unique (no accidental unique index)', function (): void {
    $requester = User::factory()->create();
    Ticket::create(['title' => 'Uno', 'requester_id' => $requester->id, 'status_changed_at' => now()]);

    expect(fn () => Ticket::create([
        'title' => 'Due', 'requester_id' => $requester->id, 'status_changed_at' => now(),
    ]))->not->toThrow(QueryException::class);
});
