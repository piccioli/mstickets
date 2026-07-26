<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Queries\ActiveRequestsQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('includes tickets with a requester in an active status', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewAny);
    $requester = userWithPermissions();
    $active = ticket(['requester_id' => $requester->id, 'status' => TicketStatus::Progress]);

    expect(ActiveRequestsQuery::for($actor)->pluck('id'))->toContain($active->id);
});

test('excludes tickets without a requester', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewAny);
    $noRequester = ticket(['requester_id' => null, 'status' => TicketStatus::Progress]);

    expect(ActiveRequestsQuery::for($actor)->pluck('id'))->not->toContain($noRequester->id);
});

test('excludes done, backlog and rejected tickets', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewAny);
    $requester = userWithPermissions();
    $done = ticket(['requester_id' => $requester->id, 'status' => TicketStatus::Done]);
    $backlog = ticket(['requester_id' => $requester->id, 'status' => TicketStatus::Backlog]);
    $rejected = ticket(['requester_id' => $requester->id, 'status' => TicketStatus::Rejected]);

    $visible = ActiveRequestsQuery::for($actor)->pluck('id');

    expect($visible)->not->toContain($done->id, $backlog->id, $rejected->id);
});
