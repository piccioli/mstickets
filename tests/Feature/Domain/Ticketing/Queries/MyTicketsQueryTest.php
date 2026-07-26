<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Queries\MyTicketsQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('includes the customer own tickets that are not done or rejected', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewOwn);
    $mine = ticket(['requester_id' => $actor->id, 'status' => TicketStatus::Progress]);

    expect(MyTicketsQuery::for($actor)->pluck('id'))->toContain($mine->id);
});

test('excludes other requesters tickets and the actor own done/rejected tickets', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewOwn);
    $someoneElse = userWithPermissions();
    $notMine = ticket(['requester_id' => $someoneElse->id, 'status' => TicketStatus::Progress]);
    $done = ticket(['requester_id' => $actor->id, 'status' => TicketStatus::Done]);
    $rejected = ticket(['requester_id' => $actor->id, 'status' => TicketStatus::Rejected]);

    $visible = MyTicketsQuery::for($actor)->pluck('id');

    expect($visible)->not->toContain($notMine->id, $done->id, $rejected->id);
});
