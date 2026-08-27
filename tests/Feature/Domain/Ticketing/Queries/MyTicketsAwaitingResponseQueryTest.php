<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Queries\MyTicketsAwaitingResponseQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('includes the requester own tickets in waiting or problem status', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewOwn);
    $waiting = ticket(['requester_id' => $actor->id, 'status' => TicketStatus::Waiting]);
    $problem = ticket(['requester_id' => $actor->id, 'status' => TicketStatus::Problem]);

    $visible = MyTicketsAwaitingResponseQuery::for($actor)->pluck('id');

    expect($visible)->toContain($waiting->id, $problem->id);
});

test('excludes other requesters tickets and the actor own tickets in other statuses', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewOwn);
    $someoneElse = userWithPermissions();
    $notMine = ticket(['requester_id' => $someoneElse->id, 'status' => TicketStatus::Waiting]);
    $progress = ticket(['requester_id' => $actor->id, 'status' => TicketStatus::Progress]);

    $visible = MyTicketsAwaitingResponseQuery::for($actor)->pluck('id');

    expect($visible)->not->toContain($notMine->id, $progress->id);
});
