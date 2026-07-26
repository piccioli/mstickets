<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a user without ticket.* permissions is denied every TicketPolicy ability', function (): void {
    $actor = userWithPermissions();
    $ticket = ticket();

    expect($actor->can('viewAny', Ticket::class))->toBeFalse()
        ->and($actor->can('view', $ticket))->toBeFalse()
        ->and($actor->can('create', Ticket::class))->toBeFalse()
        ->and($actor->can('update', $ticket))->toBeFalse()
        ->and($actor->can('delete', $ticket))->toBeFalse()
        ->and($actor->can('restore', $ticket))->toBeFalse()
        ->and($actor->can('forceDelete', $ticket))->toBeFalse();
});

test('a user with the matching permission is authorized for create/delete', function (): void {
    $ticket = ticket();

    expect(userWithPermissions(PermissionEnum::TicketCreate)->can('create', Ticket::class))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::TicketDelete)->can('delete', $ticket))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::TicketDelete)->can('restore', $ticket))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::TicketDelete)->can('forceDelete', $ticket))->toBeTrue();
});

// --- §9.5: view() ---

test('a customer (ticket.view.own) is denied a ticket whose requester_id is not their own', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewOwn);
    $someoneElse = userWithPermissions();
    $ticket = ticket(['requester_id' => $someoneElse->id]);

    expect($actor->can('viewAny', Ticket::class))->toBeTrue()
        ->and($actor->can('view', $ticket))->toBeFalse();
});

test('a customer (ticket.view.own) is allowed a ticket whose requester_id is their own', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewOwn);
    $ticket = ticket(['requester_id' => $actor->id]);

    expect($actor->can('view', $ticket))->toBeTrue();
});

test('ticket.view.any is not constrained by requester_id/assignee_id/tester_id', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewAny);
    $someoneElse = userWithPermissions();
    $ticket = ticket(['requester_id' => $someoneElse->id]);

    expect($actor->can('view', $ticket))->toBeTrue();
});

test('a developer (ticket.view.assigned) sees a ticket only if assignee or tester', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewAssigned);
    $someoneElse = userWithPermissions();

    expect($actor->can('view', ticket(['assignee_id' => $someoneElse->id])))->toBeFalse()
        ->and($actor->can('view', ticket(['assignee_id' => $actor->id])))->toBeTrue()
        ->and($actor->can('view', ticket(['tester_id' => $actor->id])))->toBeTrue();
});

// --- §9.5: update() ---

test('a developer (ticket.update.assigned) is denied a ticket they are neither assignee nor tester of', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketUpdateAssigned);
    $someoneElse = userWithPermissions();
    $ticket = ticket(['assignee_id' => $someoneElse->id]);

    expect($actor->can('update', $ticket))->toBeFalse();
});

test('a developer (ticket.update.assigned) is allowed a ticket they are assignee or tester of', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketUpdateAssigned);

    expect($actor->can('update', ticket(['assignee_id' => $actor->id])))->toBeTrue()
        ->and($actor->can('update', ticket(['tester_id' => $actor->id])))->toBeTrue();
});

test('a customer (ticket.update.own) is denied a ticket whose requester_id is not their own', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketUpdateOwn);
    $someoneElse = userWithPermissions();
    $ticket = ticket(['requester_id' => $someoneElse->id]);

    expect($actor->can('update', $ticket))->toBeFalse();
});

test('a customer (ticket.update.own) is allowed a ticket whose requester_id is their own', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketUpdateOwn);
    $ticket = ticket(['requester_id' => $actor->id]);

    expect($actor->can('update', $ticket))->toBeTrue();
});

test('ticket.update.any (admin/manager) is not constrained by any record relationship', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketUpdateAny);
    $someoneElse = userWithPermissions();
    $ticket = ticket(['requester_id' => $someoneElse->id, 'assignee_id' => $someoneElse->id]);

    expect($actor->can('update', $ticket))->toBeTrue();
});
