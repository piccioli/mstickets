<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Queries\ToTestByMeQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('includes tickets in testing where the actor is the tester', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewAssigned);
    $mine = ticket(['tester_id' => $actor->id, 'status' => TicketStatus::Testing]);

    expect(ToTestByMeQuery::for($actor)->pluck('id'))->toContain($mine->id);
});

test('excludes tickets tested by someone else, and non-testing tickets tested by the actor', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewAssigned);
    $someoneElse = userWithPermissions();
    $notMine = ticket(['tester_id' => $someoneElse->id, 'status' => TicketStatus::Testing]);
    $notTesting = ticket(['tester_id' => $actor->id, 'status' => TicketStatus::Progress]);

    $visible = ToTestByMeQuery::for($actor)->pluck('id');

    expect($visible)->not->toContain($notMine->id, $notTesting->id);
});
