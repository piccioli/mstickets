<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Queries\AllCustomerTicketsQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('includes tickets whose requester has the customer role, regardless of status', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewAny);
    $customer = withRole(userWithPermissions(), UserRole::Customer);
    $ticket = ticket(['requester_id' => $customer->id, 'status' => TicketStatus::Done]);

    expect(AllCustomerTicketsQuery::for($actor)->pluck('id'))->toContain($ticket->id);
});

test('excludes tickets whose requester does not have the customer role', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewAny);
    $developer = withRole(userWithPermissions(), UserRole::Developer);
    $ticket = ticket(['requester_id' => $developer->id, 'status' => TicketStatus::New]);

    expect(AllCustomerTicketsQuery::for($actor)->pluck('id'))->not->toContain($ticket->id);
});
