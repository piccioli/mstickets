<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('ticket.view.any sees every ticket regardless of requester/assignee/tester', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewAny);
    $someoneElse = userWithPermissions();
    $mine = ticket(['requester_id' => $actor->id]);
    $theirs = ticket(['requester_id' => $someoneElse->id, 'assignee_id' => $someoneElse->id]);

    $visible = Ticket::query()->visibleTo($actor)->pluck('id');

    expect($visible)->toContain($mine->id, $theirs->id);
});

test('ticket.view.assigned (developer/manager) sees only tickets where assignee or tester', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewAssigned);
    $someoneElse = userWithPermissions();
    $assignedToMe = ticket(['assignee_id' => $actor->id]);
    $testingByMe = ticket(['tester_id' => $actor->id]);
    $notMine = ticket(['assignee_id' => $someoneElse->id]);

    $visible = Ticket::query()->visibleTo($actor)->pluck('id');

    expect($visible)->toContain($assignedToMe->id)
        ->toContain($testingByMe->id)
        ->not->toContain($notMine->id);
});

test('ticket.view.own (customer) sees only their own tickets', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewOwn);
    $someoneElse = userWithPermissions();
    $mine = ticket(['requester_id' => $actor->id]);
    $assignedToMeButNotMine = ticket(['assignee_id' => $actor->id]);
    $someoneElsesTicket = ticket(['requester_id' => $someoneElse->id]);

    $visible = Ticket::query()->visibleTo($actor)->pluck('id');

    expect($visible)->toContain($mine->id)
        ->not->toContain($assignedToMeButNotMine->id)
        ->not->toContain($someoneElsesTicket->id);
});

test('a user with no ticket.view.* permission sees no ticket at all', function (): void {
    $actor = userWithPermissions();
    ticket(['requester_id' => $actor->id, 'assignee_id' => $actor->id, 'tester_id' => $actor->id]);

    expect(Ticket::query()->visibleTo($actor)->count())->toBe(0);
});
