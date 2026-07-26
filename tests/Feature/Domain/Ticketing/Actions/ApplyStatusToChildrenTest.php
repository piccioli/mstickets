<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Ticketing\Actions\ApplyStatusToChildren;
use App\Domain\Ticketing\Actions\ChangeTicketStatus;
use App\Domain\Ticketing\Enums\TicketLogEvent;
use App\Domain\Ticketing\Enums\TicketStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('propagates the status change to every direct child that allows the transition, each with its own log', function (): void {
    $admin = userWithPermissions(PermissionEnum::TicketTransitionAny);
    $parent = ticket(['status' => TicketStatus::New]);
    $child1 = ticket(['status' => TicketStatus::New, 'parent_id' => $parent->id]);
    $child2 = ticket(['status' => TicketStatus::New, 'parent_id' => $parent->id]);

    $result = ApplyStatusToChildren::run($parent, TicketStatus::Backlog, $admin);

    expect($result->applied)->toHaveCount(2)
        ->and($result->skipped)->toBe([])
        ->and($child1->fresh()->status)->toBe(TicketStatus::Backlog)
        ->and($child2->fresh()->status)->toBe(TicketStatus::Backlog);

    expect($child1->fresh()->logs()->sole()->event)->toBe(TicketLogEvent::StatusChanged)
        ->and($child1->fresh()->logs()->sole()->to_status)->toBe(TicketStatus::Backlog)
        ->and($child2->fresh()->logs()->sole()->to_status)->toBe(TicketStatus::Backlog);
});

test('a child whose transition is not allowed is skipped, with a reason, without blocking the others', function (): void {
    $admin = userWithPermissions(PermissionEnum::TicketTransitionAny);
    $parent = ticket(['status' => TicketStatus::New]);
    $allowedChild = ticket(['status' => TicketStatus::New, 'parent_id' => $parent->id]);
    $forbiddenChild = ticket(['status' => TicketStatus::Done, 'parent_id' => $parent->id]);

    $result = ApplyStatusToChildren::run($parent, TicketStatus::Backlog, $admin);

    expect($result->applied)->toHaveCount(1)
        ->and($result->applied[0]->id)->toBe($allowedChild->id)
        ->and($allowedChild->fresh()->status)->toBe(TicketStatus::Backlog);

    expect($result->skipped)->toHaveCount(1)
        ->and($result->skipped[0]['ticket']->id)->toBe($forbiddenChild->id)
        ->and($result->skipped[0]['reason'])->toBeString()->not->toBeEmpty()
        ->and($forbiddenChild->fresh()->status)->toBe(TicketStatus::Done)
        ->and($forbiddenChild->fresh()->logs()->count())->toBe(0);
});

test('changing the parent status alone never propagates to children unless the action is invoked explicitly', function (): void {
    $admin = userWithPermissions(PermissionEnum::TicketTransitionAny);
    $parent = ticket(['status' => TicketStatus::New]);
    $child = ticket(['status' => TicketStatus::New, 'parent_id' => $parent->id]);

    ChangeTicketStatus::run($parent, TicketStatus::Backlog, $admin);

    expect($parent->fresh()->status)->toBe(TicketStatus::Backlog)
        ->and($child->fresh()->status)->toBe(TicketStatus::New)
        ->and($child->fresh()->logs()->count())->toBe(0);
});
