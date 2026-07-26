<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Actions\AssignTicket;
use App\Domain\Ticketing\Enums\TicketLogEvent;
use App\Domain\Ticketing\Events\TicketAssigned;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('assigns the ticket to the given user', function (): void {
    $actor = User::factory()->create();
    $assignee = User::factory()->create();
    $t = ticket();

    $updated = AssignTicket::run($t, $assignee, $actor);

    expect($updated->assignee_id)->toBe($assignee->id);
});

test('writes an assigned ticket_log with a typed changes DTO recording the previous and new assignee', function (): void {
    $actor = User::factory()->create();
    $firstAssignee = User::factory()->create();
    $secondAssignee = User::factory()->create();
    $t = ticket(['assignee_id' => $firstAssignee->id]);

    $updated = AssignTicket::run($t, $secondAssignee, $actor);

    $log = $updated->logs()->sole();

    expect($log->event)->toBe(TicketLogEvent::Assigned)
        ->and($log->user_id)->toBe($actor->id)
        ->and($log->changes)->toBe([
            'assignee_id' => ['from' => $firstAssignee->id, 'to' => $secondAssignee->id],
        ]);
});

test('emits TicketAssigned with previous and new assignee ids', function (): void {
    Event::fake();

    $actor = User::factory()->create();
    $assignee = User::factory()->create();
    $t = ticket();

    $updated = AssignTicket::run($t, $assignee, $actor);

    Event::assertDispatched(TicketAssigned::class, function (TicketAssigned $event) use ($updated, $assignee): bool {
        return $event->ticket->is($updated)
            && $event->previousAssigneeId === null
            && $event->assigneeId === $assignee->id;
    });
});
