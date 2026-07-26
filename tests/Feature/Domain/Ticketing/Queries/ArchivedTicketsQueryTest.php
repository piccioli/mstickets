<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Queries\ArchivedTicketsQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('includes done and rejected tickets, with or without a requester', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewAny);
    $requester = userWithPermissions();
    $done = ticket(['requester_id' => $requester->id, 'status' => TicketStatus::Done]);
    $rejected = ticket(['requester_id' => null, 'status' => TicketStatus::Rejected]);

    $visible = ArchivedTicketsQuery::for($actor)->pluck('id');

    expect($visible)->toContain($done->id, $rejected->id);
});

test('excludes tickets in any non-archived status', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewAny);
    $requester = userWithPermissions();
    $progress = ticket(['requester_id' => $requester->id, 'status' => TicketStatus::Progress]);

    expect(ArchivedTicketsQuery::for($actor)->pluck('id'))->not->toContain($progress->id);
});
