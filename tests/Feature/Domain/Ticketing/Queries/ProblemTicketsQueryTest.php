<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Queries\ProblemTicketsQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('includes active requests in problem status', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewAny);
    $requester = userWithPermissions();
    $problem = ticket(['requester_id' => $requester->id, 'status' => TicketStatus::Problem]);

    expect(ProblemTicketsQuery::for($actor)->pluck('id'))->toContain($problem->id);
});

test('excludes tickets without a requester and tickets in other statuses', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewAny);
    $requester = userWithPermissions();
    $noRequester = ticket(['requester_id' => null, 'status' => TicketStatus::Problem]);
    $waiting = ticket(['requester_id' => $requester->id, 'status' => TicketStatus::Waiting]);

    $visible = ProblemTicketsQuery::for($actor)->pluck('id');

    expect($visible)->not->toContain($noRequester->id, $waiting->id);
});
