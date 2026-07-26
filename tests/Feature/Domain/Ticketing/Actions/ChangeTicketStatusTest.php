<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Ticketing\Actions\ChangeTicketStatus;
use App\Domain\Ticketing\Enums\TicketLogEvent;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Events\TicketStatusChanged;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('an allowed transition applies the contextual attributes, updates status_changed_at and writes a status_changed log', function (): void {
    $admin = userWithPermissions(PermissionEnum::TicketTransitionAny);
    $developer = userWithPermissions(PermissionEnum::TicketUpdateAssigned);
    $t = ticket(['status_changed_at' => now()->subDay()]);

    $updated = ChangeTicketStatus::run($t, TicketStatus::Assigned, $admin, ['assignee_id' => $developer->id]);

    expect($updated->status)->toBe(TicketStatus::Assigned)
        ->and($updated->assignee_id)->toBe($developer->id)
        ->and($updated->status_changed_at->isToday())->toBeTrue();

    $log = $updated->logs()->sole();

    expect($log->event)->toBe(TicketLogEvent::StatusChanged)
        ->and($log->from_status)->toBe(TicketStatus::New)
        ->and($log->to_status)->toBe(TicketStatus::Assigned)
        ->and($log->user_id)->toBe($admin->id);
});

test('a forbidden transition writes nothing and raises a localized validation error', function (): void {
    $admin = userWithPermissions(PermissionEnum::TicketTransitionAny);
    $t = ticket(['status' => TicketStatus::Done]);

    expect(fn () => ChangeTicketStatus::run($t, TicketStatus::New, $admin))
        ->toThrow(ValidationException::class);

    expect($t->fresh()->status)->toBe(TicketStatus::Done)
        ->and($t->fresh()->logs()->count())->toBe(0);
});

test('emits TicketStatusChanged with from/to', function (): void {
    Event::fake();

    $admin = userWithPermissions(PermissionEnum::TicketTransitionAny);
    $t = ticket();

    ChangeTicketStatus::run($t, TicketStatus::Backlog, $admin);

    Event::assertDispatched(
        TicketStatusChanged::class,
        fn (TicketStatusChanged $event): bool => $event->from === TicketStatus::New && $event->to === TicketStatus::Backlog,
    );
});

test('the system actor can restore a ticket from waiting to its previous status', function (): void {
    $developer = userWithPermissions(PermissionEnum::TicketUpdateAssigned);
    $t = ticket(['status' => TicketStatus::Todo, 'assignee_id' => $developer->id]);

    ChangeTicketStatus::run($t, TicketStatus::Waiting, $developer, ['waiting_reason' => 'In attesa del cliente']);

    $system = systemUser();

    $restored = ChangeTicketStatus::run($t->fresh(), TicketStatus::Todo, $system);

    expect($restored->status)->toBe(TicketStatus::Todo)
        ->and($restored->previous_status)->toBeNull();
});

// --- REGOLA §6.1.4: un solo ticket in progress per assegnatario --------------------

test('moving a ticket to progress demotes the assignee\'s other in-progress tickets to todo, each with its own log', function (): void {
    $admin = userWithPermissions(PermissionEnum::TicketTransitionAny);
    $developer = userWithPermissions(PermissionEnum::TicketUpdateAssigned);

    $alreadyInProgress1 = ticket(['status' => TicketStatus::Progress, 'assignee_id' => $developer->id]);
    $alreadyInProgress2 = ticket(['status' => TicketStatus::Progress, 'assignee_id' => $developer->id]);
    $otherAssigneeInProgress = ticket(['status' => TicketStatus::Progress, 'assignee_id' => $admin->id]);
    $target = ticket(['status' => TicketStatus::Todo, 'assignee_id' => $developer->id]);

    ChangeTicketStatus::run($target, TicketStatus::Progress, $admin);

    expect($target->fresh()->status)->toBe(TicketStatus::Progress)
        ->and($alreadyInProgress1->fresh()->status)->toBe(TicketStatus::Todo)
        ->and($alreadyInProgress2->fresh()->status)->toBe(TicketStatus::Todo)
        ->and($otherAssigneeInProgress->fresh()->status)->toBe(TicketStatus::Progress);

    expect($alreadyInProgress1->fresh()->logs()->sole()->event)->toBe(TicketLogEvent::StatusChanged)
        ->and($alreadyInProgress1->fresh()->logs()->sole()->to_status)->toBe(TicketStatus::Todo)
        ->and($alreadyInProgress2->fresh()->logs()->sole()->to_status)->toBe(TicketStatus::Todo);
});

test('no demotion happens when the transition does not carry the DemoteOtherProgressTickets effect', function (): void {
    $admin = userWithPermissions(PermissionEnum::TicketTransitionAny);
    $developer = userWithPermissions(PermissionEnum::TicketUpdateAssigned);

    $alreadyInProgress = ticket(['status' => TicketStatus::Progress, 'assignee_id' => $developer->id]);
    $target = ticket(['status' => TicketStatus::New, 'assignee_id' => $developer->id]);

    ChangeTicketStatus::run($target, TicketStatus::Backlog, $admin);

    expect($alreadyInProgress->fresh()->status)->toBe(TicketStatus::Progress);
});

test('the whole transition rolls back if demoting another in-progress ticket fails', function (): void {
    $admin = userWithPermissions(PermissionEnum::TicketTransitionAny);
    $developer = userWithPermissions(PermissionEnum::TicketUpdateAssigned);

    $alreadyInProgress = ticket(['status' => TicketStatus::Progress, 'assignee_id' => $developer->id]);
    $target = ticket(['status' => TicketStatus::Todo, 'assignee_id' => $developer->id]);

    Event::listen(TicketStatusChanged::class, function (TicketStatusChanged $event) use ($alreadyInProgress): void {
        if ($event->ticket->is($alreadyInProgress)) {
            throw new RuntimeException('Simulated failure while demoting.');
        }
    });

    expect(fn () => ChangeTicketStatus::run($target, TicketStatus::Progress, $admin))
        ->toThrow(RuntimeException::class);

    expect($target->fresh()->status)->toBe(TicketStatus::Todo)
        ->and($alreadyInProgress->fresh()->status)->toBe(TicketStatus::Progress)
        ->and($target->fresh()->logs()->count())->toBe(0)
        ->and($alreadyInProgress->fresh()->logs()->count())->toBe(0);
});
