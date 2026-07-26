<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Queries\InTestingQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('includes any active request in testing, regardless of tester', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewAny);
    $requester = userWithPermissions();
    $someoneElse = userWithPermissions();
    $inTesting = ticket([
        'requester_id' => $requester->id,
        'tester_id' => $someoneElse->id,
        'status' => TicketStatus::Testing,
    ]);

    expect(InTestingQuery::for($actor)->pluck('id'))->toContain($inTesting->id);
});

test('excludes tickets without a requester and tickets in other statuses', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewAny);
    $requester = userWithPermissions();
    $noRequester = ticket(['requester_id' => null, 'status' => TicketStatus::Testing]);
    $progress = ticket(['requester_id' => $requester->id, 'status' => TicketStatus::Progress]);

    $visible = InTestingQuery::for($actor)->pluck('id');

    expect($visible)->not->toContain($noRequester->id, $progress->id);
});
