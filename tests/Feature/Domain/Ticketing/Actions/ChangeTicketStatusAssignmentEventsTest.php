<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Ticketing\Actions\ChangeTicketStatus;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Events\TicketAssigned;
use App\Domain\Ticketing\Events\TicketTesterAssigned;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('emits TicketAssigned when a transition sets assignee_id via context', function (): void {
    $admin = userWithPermissions(PermissionEnum::TicketTransitionAny);
    $developer = userWithPermissions(PermissionEnum::TicketUpdateAssigned);
    $t = ticket();

    Event::fake();

    $updated = ChangeTicketStatus::run($t, TicketStatus::Assigned, $admin, ['assignee_id' => $developer->id]);

    Event::assertDispatched(TicketAssigned::class, function (TicketAssigned $event) use ($updated, $developer, $admin): bool {
        return $event->ticket->is($updated)
            && $event->previousAssigneeId === null
            && $event->assigneeId === $developer->id
            && $event->actor->is($admin);
    });
});

test('emits TicketTesterAssigned when a transition sets tester_id via context', function (): void {
    $admin = userWithPermissions(PermissionEnum::TicketTransitionAny);
    $developer = userWithPermissions(PermissionEnum::TicketUpdateAssigned);
    $tester = userWithPermissions(PermissionEnum::TicketUpdateAssigned);
    $t = ticket(['status' => TicketStatus::Progress, 'assignee_id' => $developer->id]);

    Event::fake();

    $updated = ChangeTicketStatus::run($t, TicketStatus::Testing, $admin, ['tester_id' => $tester->id]);

    Event::assertDispatched(TicketTesterAssigned::class, function (TicketTesterAssigned $event) use ($updated, $tester, $admin): bool {
        return $event->ticket->is($updated)
            && $event->previousTesterId === null
            && $event->testerId === $tester->id
            && $event->actor->is($admin);
    });
});

test('does not emit TicketAssigned when the transition does not touch assignee_id', function (): void {
    $admin = userWithPermissions(PermissionEnum::TicketTransitionAny);
    $developer = userWithPermissions(PermissionEnum::TicketUpdateAssigned);
    $t = ticket(['status' => TicketStatus::Assigned, 'assignee_id' => $developer->id]);

    Event::fake();

    ChangeTicketStatus::run($t, TicketStatus::Todo, $admin);

    Event::assertNotDispatched(TicketAssigned::class);
});

test('does not emit TicketAssigned when the context assignee_id equals the current one', function (): void {
    $admin = userWithPermissions(PermissionEnum::TicketTransitionAny);
    $developer = userWithPermissions(PermissionEnum::TicketUpdateAssigned);
    $t = ticket(['status' => TicketStatus::Backlog, 'assignee_id' => $developer->id]);

    Event::fake();

    ChangeTicketStatus::run($t, TicketStatus::Todo, $admin, ['assignee_id' => $developer->id]);

    Event::assertNotDispatched(TicketAssigned::class);
});
