<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Ticketing\Actions\ChangeTicketStatus;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\TimeTracking\Jobs\RecalculateTicketWorkedTimeJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('a status change queues a worked-time recalculation job for the ticket', function (): void {
    Queue::fake();

    $admin = userWithPermissions(PermissionEnum::TicketTransitionAny);
    $t = ticket();

    ChangeTicketStatus::run($t, TicketStatus::Backlog, $admin);

    Queue::assertPushed(
        RecalculateTicketWorkedTimeJob::class,
        fn (RecalculateTicketWorkedTimeJob $job): bool => $job->ticketId === $t->id,
    );
});

test('debounces a burst of transitions on the same ticket into a single queued job', function (): void {
    Queue::fake();

    $admin = userWithPermissions(PermissionEnum::TicketTransitionAny);
    $t = ticket();

    ChangeTicketStatus::run($t, TicketStatus::Backlog, $admin);
    ChangeTicketStatus::run($t->fresh(), TicketStatus::Assigned, $admin, ['assignee_id' => $admin->id]);
    ChangeTicketStatus::run($t->fresh(), TicketStatus::Todo, $admin);

    Queue::assertPushed(RecalculateTicketWorkedTimeJob::class, 1);
});

test('queues a new job again once the debounce window has elapsed', function (): void {
    Queue::fake();

    $admin = userWithPermissions(PermissionEnum::TicketTransitionAny);
    $t = ticket();

    ChangeTicketStatus::run($t, TicketStatus::Backlog, $admin);

    $this->travel(10)->seconds();

    ChangeTicketStatus::run($t->fresh(), TicketStatus::Assigned, $admin, ['assignee_id' => $admin->id]);

    Queue::assertPushed(RecalculateTicketWorkedTimeJob::class, 2);
});

test('debounce keys are independent per ticket', function (): void {
    Queue::fake();

    $admin = userWithPermissions(PermissionEnum::TicketTransitionAny);
    $t1 = ticket();
    $t2 = ticket();

    ChangeTicketStatus::run($t1, TicketStatus::Backlog, $admin);
    ChangeTicketStatus::run($t2, TicketStatus::Backlog, $admin);

    Queue::assertPushed(RecalculateTicketWorkedTimeJob::class, 2);
});
