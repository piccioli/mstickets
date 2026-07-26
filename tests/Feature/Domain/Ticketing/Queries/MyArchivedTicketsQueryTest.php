<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Queries\MyArchivedTicketsQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('includes the customer own done and rejected tickets', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewOwn);
    $done = ticket(['requester_id' => $actor->id, 'status' => TicketStatus::Done]);
    $rejected = ticket(['requester_id' => $actor->id, 'status' => TicketStatus::Rejected]);

    $visible = MyArchivedTicketsQuery::for($actor)->pluck('id');

    expect($visible)->toContain($done->id, $rejected->id);
});

test('excludes other requesters archived tickets and the actor own non-archived tickets', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewOwn);
    $someoneElse = userWithPermissions();
    $notMine = ticket(['requester_id' => $someoneElse->id, 'status' => TicketStatus::Done]);
    $stillOpen = ticket(['requester_id' => $actor->id, 'status' => TicketStatus::Progress]);

    $visible = MyArchivedTicketsQuery::for($actor)->pluck('id');

    expect($visible)->not->toContain($notMine->id, $stillOpen->id);
});
