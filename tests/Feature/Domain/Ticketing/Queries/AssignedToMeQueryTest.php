<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Queries\AssignedToMeQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('includes tickets assigned to the actor that are neither new nor done', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewAssigned);
    $mine = ticket(['assignee_id' => $actor->id, 'status' => TicketStatus::Progress]);

    expect(AssignedToMeQuery::for($actor)->pluck('id'))->toContain($mine->id);
});

test('excludes tickets assigned to someone else, and new/done tickets assigned to the actor', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewAssigned);
    $someoneElse = userWithPermissions();
    $notMine = ticket(['assignee_id' => $someoneElse->id, 'status' => TicketStatus::Progress]);
    $new = ticket(['assignee_id' => $actor->id, 'status' => TicketStatus::New]);
    $done = ticket(['assignee_id' => $actor->id, 'status' => TicketStatus::Done]);

    $visible = AssignedToMeQuery::for($actor)->pluck('id');

    expect($visible)->not->toContain($notMine->id, $new->id, $done->id);
});
