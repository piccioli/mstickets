<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Queries\NewTicketsQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('includes new tickets with a requester', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewAny);
    $requester = userWithPermissions();
    $new = ticket(['requester_id' => $requester->id, 'status' => TicketStatus::New]);

    expect(NewTicketsQuery::for($actor)->pluck('id'))->toContain($new->id);
});

test('excludes new tickets without a requester and tickets in other statuses', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewAny);
    $requester = userWithPermissions();
    $noRequester = ticket(['requester_id' => null, 'status' => TicketStatus::New]);
    $assigned = ticket(['requester_id' => $requester->id, 'status' => TicketStatus::Assigned]);

    $visible = NewTicketsQuery::for($actor)->pluck('id');

    expect($visible)->not->toContain($noRequester->id, $assigned->id);
});
