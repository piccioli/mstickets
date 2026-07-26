<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Queries\InProgressTicketsQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('includes any in-progress ticket with a requester, regardless of assignee', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewAny);
    $requester = userWithPermissions();
    $someoneElse = userWithPermissions();
    $inProgress = ticket([
        'requester_id' => $requester->id,
        'assignee_id' => $someoneElse->id,
        'status' => TicketStatus::Progress,
    ]);

    expect(InProgressTicketsQuery::for($actor)->pluck('id'))->toContain($inProgress->id);
});

test('excludes in-progress tickets without a requester and tickets in other statuses', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewAny);
    $requester = userWithPermissions();
    $noRequester = ticket(['requester_id' => null, 'status' => TicketStatus::Progress]);
    $todo = ticket(['requester_id' => $requester->id, 'status' => TicketStatus::Todo]);

    $visible = InProgressTicketsQuery::for($actor)->pluck('id');

    expect($visible)->not->toContain($noRequester->id, $todo->id);
});
