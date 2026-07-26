<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Queries\WaitingQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('includes active requests in waiting status', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewAny);
    $requester = userWithPermissions();
    $waiting = ticket([
        'requester_id' => $requester->id,
        'status' => TicketStatus::Waiting,
        'status_changed_at' => now(),
    ]);

    expect(WaitingQuery::for($actor)->pluck('id'))->toContain($waiting->id);
});

test('excludes tickets without a requester and tickets in other statuses', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewAny);
    $requester = userWithPermissions();
    $noRequester = ticket(['requester_id' => null, 'status' => TicketStatus::Waiting]);
    $progress = ticket(['requester_id' => $requester->id, 'status' => TicketStatus::Progress]);

    $visible = WaitingQuery::for($actor)->pluck('id');

    expect($visible)->not->toContain($noRequester->id, $progress->id);
});

test('orders the oldest waiting ticket first (ascending status_changed_at)', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewAny);
    $requester = userWithPermissions();
    $newest = ticket([
        'requester_id' => $requester->id,
        'status' => TicketStatus::Waiting,
        'status_changed_at' => now()->subDay(),
    ]);
    $oldest = ticket([
        'requester_id' => $requester->id,
        'status' => TicketStatus::Waiting,
        'status_changed_at' => now()->subWeek(),
    ]);

    expect(WaitingQuery::for($actor)->pluck('id')->all())->toBe([$oldest->id, $newest->id]);
});
