<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Queries\BacklogQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('includes backlog tickets with a requester', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewAny);
    $requester = userWithPermissions();
    $backlog = ticket(['requester_id' => $requester->id, 'status' => TicketStatus::Backlog]);

    expect(BacklogQuery::for($actor)->pluck('id'))->toContain($backlog->id);
});

test('excludes backlog tickets without a requester and tickets in other statuses', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewAny);
    $requester = userWithPermissions();
    $noRequester = ticket(['requester_id' => null, 'status' => TicketStatus::Backlog]);
    $new = ticket(['requester_id' => $requester->id, 'status' => TicketStatus::New]);

    $visible = BacklogQuery::for($actor)->pluck('id');

    expect($visible)->not->toContain($noRequester->id, $new->id);
});
