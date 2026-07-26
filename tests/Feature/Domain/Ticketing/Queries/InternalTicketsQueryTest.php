<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Queries\InternalTicketsQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('includes tickets whose requester has no customer role and are not done', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewAny);
    $developer = withRole(userWithPermissions(), UserRole::Developer);
    $internal = ticket(['requester_id' => $developer->id, 'status' => TicketStatus::Progress]);

    expect(InternalTicketsQuery::for($actor)->pluck('id'))->toContain($internal->id);
});

test('excludes tickets requested by a customer, without a requester, or already done', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewAny);
    $customer = withRole(userWithPermissions(), UserRole::Customer);
    $developer = withRole(userWithPermissions(), UserRole::Developer);
    $fromCustomer = ticket(['requester_id' => $customer->id, 'status' => TicketStatus::Progress]);
    $noRequester = ticket(['requester_id' => null, 'status' => TicketStatus::Progress]);
    $done = ticket(['requester_id' => $developer->id, 'status' => TicketStatus::Done]);

    $visible = InternalTicketsQuery::for($actor)->pluck('id');

    expect($visible)->not->toContain($fromCustomer->id, $noRequester->id, $done->id);
});
