<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a user without ticket.* permissions is denied every TicketPolicy ability', function (): void {
    $actor = userWithPermissions();
    $ticket = Ticket::create(['title' => 'Errore login', 'status_changed_at' => now()]);

    expect($actor->can('viewAny', Ticket::class))->toBeFalse()
        ->and($actor->can('view', $ticket))->toBeFalse()
        ->and($actor->can('create', Ticket::class))->toBeFalse()
        ->and($actor->can('update', $ticket))->toBeFalse()
        ->and($actor->can('delete', $ticket))->toBeFalse()
        ->and($actor->can('restore', $ticket))->toBeFalse()
        ->and($actor->can('forceDelete', $ticket))->toBeFalse();
});

test('a user with ticket.view.own can view but not update tickets', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewOwn);
    $ticket = Ticket::create(['title' => 'Errore login', 'status_changed_at' => now()]);

    expect($actor->can('viewAny', Ticket::class))->toBeTrue()
        ->and($actor->can('view', $ticket))->toBeTrue()
        ->and($actor->can('update', $ticket))->toBeFalse();
});

test('a user with the matching permission is authorized for create/update/delete', function (): void {
    $ticket = Ticket::create(['title' => 'Errore login', 'status_changed_at' => now()]);

    expect(userWithPermissions(PermissionEnum::TicketCreate)->can('create', Ticket::class))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::TicketUpdateAssigned)->can('update', $ticket))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::TicketDelete)->can('delete', $ticket))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::TicketDelete)->can('restore', $ticket))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::TicketDelete)->can('forceDelete', $ticket))->toBeTrue();
});
