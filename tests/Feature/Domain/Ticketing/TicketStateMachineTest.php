<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Enums\TicketType;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\StateMachine\TicketStateMachine;
use App\Domain\Ticketing\StateMachine\TransitionEffect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

// --- new -> assigned --------------------------------------------------------------

test('admin can move a new ticket to assigned when assignee_id is provided in context', function (): void {
    $admin = userWithPermissions(PermissionEnum::TicketTransitionAny);
    $developer = userWithPermissions(PermissionEnum::TicketUpdateAssigned);
    $t = ticket();

    expect(TicketStateMachine::can($t, TicketStatus::Assigned, $admin, ['assignee_id' => $developer->id]))->toBeTrue();
});

test('admin cannot move a new ticket to assigned without an assignee_id anywhere', function (): void {
    $admin = userWithPermissions(PermissionEnum::TicketTransitionAny);
    $t = ticket();

    expect(TicketStateMachine::can($t, TicketStatus::Assigned, $admin))->toBeFalse();

    expect(fn () => TicketStateMachine::authorize($t, TicketStatus::Assigned, $admin))
        ->toThrow(ValidationException::class, 'La transizione richiede di specificare un assegnatario.');
});

test('a developer can self-assign a new ticket (auto-assignment)', function (): void {
    $developer = userWithPermissions(PermissionEnum::TicketUpdateAssigned);
    $t = ticket();

    expect(TicketStateMachine::can($t, TicketStatus::Assigned, $developer, ['assignee_id' => $developer->id]))->toBeTrue();
});

test('a developer cannot assign a new ticket to somebody else', function (): void {
    $developer = userWithPermissions(PermissionEnum::TicketUpdateAssigned);
    $someoneElse = userWithPermissions(PermissionEnum::TicketUpdateAssigned);
    $t = ticket();

    expect(TicketStateMachine::can($t, TicketStatus::Assigned, $developer, ['assignee_id' => $someoneElse->id]))->toBeFalse();
});

test('a user without ticket permissions cannot move a new ticket to assigned', function (): void {
    $customer = userWithPermissions();
    $t = ticket();

    expect(TicketStateMachine::can($t, TicketStatus::Assigned, $customer, ['assignee_id' => $customer->id]))->toBeFalse();
});

// --- new -> backlog (no relation required) ----------------------------------------

test('a developer can move any new ticket to backlog without being its assignee', function (): void {
    $developer = userWithPermissions(PermissionEnum::TicketUpdateAssigned);
    $t = ticket();

    expect(TicketStateMachine::can($t, TicketStatus::Backlog, $developer))->toBeTrue();
});

test('admin can move a new ticket to backlog', function (): void {
    $admin = userWithPermissions(PermissionEnum::TicketTransitionAny);
    $t = ticket();

    expect(TicketStateMachine::can($t, TicketStatus::Backlog, $admin))->toBeTrue();
});

test('a customer cannot move a new ticket to backlog', function (): void {
    $customer = userWithPermissions();
    $t = ticket();

    expect(TicketStateMachine::can($t, TicketStatus::Backlog, $customer))->toBeFalse();
});

// --- new -> rejected (admin/manager only, not developer) --------------------------

test('admin can reject a new ticket but a developer cannot', function (): void {
    $admin = userWithPermissions(PermissionEnum::TicketTransitionAny);
    $developer = userWithPermissions(PermissionEnum::TicketUpdateAssigned);
    $t = ticket();

    expect(TicketStateMachine::can($t, TicketStatus::Rejected, $admin))->toBeTrue()
        ->and(TicketStateMachine::can($t, TicketStatus::Rejected, $developer))->toBeFalse();
});

// --- backlog -> assigned / todo (auto-assignment guard again) --------------------

test('backlog to assigned and backlog to todo both require a valorized assignee', function (): void {
    $admin = userWithPermissions(PermissionEnum::TicketTransitionAny);
    $developer = userWithPermissions(PermissionEnum::TicketUpdateAssigned);
    $t = ticket(['status' => TicketStatus::Backlog]);

    expect(TicketStateMachine::can($t, TicketStatus::Assigned, $admin))->toBeFalse()
        ->and(TicketStateMachine::can($t, TicketStatus::Assigned, $developer, ['assignee_id' => $developer->id]))->toBeTrue()
        ->and(TicketStateMachine::can($t, TicketStatus::Todo, $developer, ['assignee_id' => $developer->id]))->toBeTrue()
        ->and(TicketStateMachine::can($t, TicketStatus::Todo, $developer, ['assignee_id' => $admin->id]))->toBeFalse();
});

// --- assigned -> todo (assignee relation) -----------------------------------------

test('only the assignee (or admin/manager) can move an assigned ticket to todo', function (): void {
    $manager = userWithPermissions(PermissionEnum::TicketTransitionAny);
    $assignee = userWithPermissions(PermissionEnum::TicketUpdateAssigned);
    $otherDeveloper = userWithPermissions(PermissionEnum::TicketUpdateAssigned);
    $t = ticket(['status' => TicketStatus::Assigned, 'assignee_id' => $assignee->id]);

    expect(TicketStateMachine::can($t, TicketStatus::Todo, $assignee))->toBeTrue()
        ->and(TicketStateMachine::can($t, TicketStatus::Todo, $manager))->toBeTrue()
        ->and(TicketStateMachine::can($t, TicketStatus::Todo, $otherDeveloper))->toBeFalse();
});

// --- todo -> progress (demote effect declared) ------------------------------------

test('todo to progress declares the demote-other-progress-tickets effect', function (): void {
    $assignee = userWithPermissions(PermissionEnum::TicketUpdateAssigned);
    $t = ticket(['status' => TicketStatus::Todo, 'assignee_id' => $assignee->id]);

    expect(TicketStateMachine::can($t, TicketStatus::Progress, $assignee))->toBeTrue();

    $transition = collect(TicketStateMachine::transitions())
        ->first(fn ($transition) => $transition->appliesTo(TicketStatus::Todo) && $transition->matchesTarget($t, TicketStatus::Progress));

    expect($transition->effects)->toContain(TransitionEffect::DemoteOtherProgressTickets);
});

// --- progress -> testing (tester guard) -------------------------------------------

test('progress to testing requires a valorized tester', function (): void {
    $assignee = userWithPermissions(PermissionEnum::TicketUpdateAssigned);
    $tester = userWithPermissions(PermissionEnum::TicketUpdateAssigned);
    $t = ticket(['status' => TicketStatus::Progress, 'assignee_id' => $assignee->id]);

    expect(TicketStateMachine::can($t, TicketStatus::Testing, $assignee))->toBeFalse();

    expect(fn () => TicketStateMachine::authorize($t, TicketStatus::Testing, $assignee))
        ->toThrow(ValidationException::class, 'La transizione richiede di specificare un tester.');

    expect(TicketStateMachine::can($t, TicketStatus::Testing, $assignee, ['tester_id' => $tester->id]))->toBeTrue();
});

test('progress to released and progress to todo are allowed for the assignee', function (): void {
    $assignee = userWithPermissions(PermissionEnum::TicketUpdateAssigned);
    $t = ticket(['status' => TicketStatus::Progress, 'assignee_id' => $assignee->id]);

    expect(TicketStateMachine::can($t, TicketStatus::Released, $assignee))->toBeTrue();

    $released = collect(TicketStateMachine::transitions())
        ->first(fn ($transition) => $transition->appliesTo(TicketStatus::Progress) && $transition->matchesTarget($t, TicketStatus::Released));
    expect($released->effects)->toContain(TransitionEffect::SetReleasedAt);

    expect(TicketStateMachine::can($t, TicketStatus::Todo, $assignee))->toBeTrue();
});

// --- testing -> tested/todo/rejected (tester only, NOT the assignee) --------------

test('only the tester (or admin/manager) can resolve a ticket in testing, not the assignee', function (): void {
    $assignee = userWithPermissions(PermissionEnum::TicketUpdateAssigned);
    $tester = userWithPermissions(PermissionEnum::TicketUpdateAssigned);
    $manager = userWithPermissions(PermissionEnum::TicketTransitionAny);
    $t = ticket(['status' => TicketStatus::Testing, 'assignee_id' => $assignee->id, 'tester_id' => $tester->id]);

    foreach ([TicketStatus::Tested, TicketStatus::Todo, TicketStatus::Rejected] as $target) {
        expect(TicketStateMachine::can($t, $target, $tester))->toBeTrue()
            ->and(TicketStateMachine::can($t, $target, $manager))->toBeTrue()
            ->and(TicketStateMachine::can($t, $target, $assignee))->toBeFalse();
    }
});

// --- tested -> released (assignee only, NOT the tester) ---------------------------

test('only the assignee (or admin/manager) can release a tested ticket, not the tester', function (): void {
    $assignee = userWithPermissions(PermissionEnum::TicketUpdateAssigned);
    $tester = userWithPermissions(PermissionEnum::TicketUpdateAssigned);
    $t = ticket(['status' => TicketStatus::Tested, 'assignee_id' => $assignee->id, 'tester_id' => $tester->id]);

    expect(TicketStateMachine::can($t, TicketStatus::Released, $assignee))->toBeTrue()
        ->and(TicketStateMachine::can($t, TicketStatus::Released, $tester))->toBeFalse();
});

// --- released -> done (assignee, and system since US-610/T4) ---------------------

test('released to done is allowed for the assignee and for the system user (T4 automation, US-610)', function (): void {
    $assignee = userWithPermissions(PermissionEnum::TicketUpdateAssigned);
    $system = systemUser();
    $t = ticket(['status' => TicketStatus::Released, 'assignee_id' => $assignee->id]);

    expect(TicketStateMachine::can($t, TicketStatus::Done, $assignee))->toBeTrue()
        ->and(TicketStateMachine::can($t, TicketStatus::Done, $system))->toBeTrue();

    $transition = collect(TicketStateMachine::transitions())
        ->first(fn ($transition) => $transition->appliesTo(TicketStatus::Released) && $transition->matchesTarget($t, TicketStatus::Done));
    expect($transition->effects)->toContain(TransitionEffect::SetDoneAt);
});

// --- * -> done, guarded by type = scrum (system only, T5, US-611) ----------------

test('any status to done is allowed for the system user on a scrum ticket, and only for the system user', function (TicketStatus $from): void {
    $admin = userWithPermissions(PermissionEnum::TicketTransitionAny);
    $system = systemUser();
    $t = ticket(['type' => TicketType::Scrum, 'status' => $from]);

    expect(TicketStateMachine::can($t, TicketStatus::Done, $system))->toBeTrue()
        ->and(TicketStateMachine::can($t, TicketStatus::Done, $admin))->toBeFalse();

    $transition = collect(TicketStateMachine::transitions())
        ->first(fn ($transition) => $transition->appliesTo($from) && $transition->matchesTarget($t, TicketStatus::Done));
    expect($transition->effects)->toContain(TransitionEffect::SetDoneAt);
})->with([
    TicketStatus::New,
    TicketStatus::Backlog,
    TicketStatus::Assigned,
    TicketStatus::Todo,
    TicketStatus::Progress,
    TicketStatus::Testing,
    TicketStatus::Tested,
    TicketStatus::Waiting,
    TicketStatus::Problem,
    TicketStatus::Rejected,
]);

test('the system user cannot move a non-scrum ticket to done via T5', function (): void {
    $system = systemUser();
    $t = ticket(['type' => TicketType::Bug, 'status' => TicketStatus::Todo]);

    expect(TicketStateMachine::can($t, TicketStatus::Done, $system))->toBeFalse();

    expect(fn () => TicketStateMachine::authorize($t, TicketStatus::Done, $system))
        ->toThrow(ValidationException::class, 'La transizione è riservata ai ticket di tipo "scrum".');
});

// --- {new,backlog,assigned,todo,progress} -> waiting (reason guard) --------------

test('waiting requires a non-empty waiting_reason from any of its allowed origin statuses', function (TicketStatus $from): void {
    $assignee = userWithPermissions(PermissionEnum::TicketUpdateAssigned);
    $t = ticket(['status' => $from, 'assignee_id' => $from === TicketStatus::New ? null : $assignee->id]);

    // From "new"/"backlog" the ticket has no assignee yet, so only admin/manager qualifies.
    $actor = in_array($from, [TicketStatus::New, TicketStatus::Backlog], strict: true)
        ? userWithPermissions(PermissionEnum::TicketTransitionAny)
        : $assignee;

    expect(TicketStateMachine::can($t, TicketStatus::Waiting, $actor))->toBeFalse();

    expect(fn () => TicketStateMachine::authorize($t, TicketStatus::Waiting, $actor))
        ->toThrow(ValidationException::class, 'Il motivo dell\'attesa è obbligatorio.');

    expect(TicketStateMachine::can($t, TicketStatus::Waiting, $actor, ['waiting_reason' => 'In attesa di risposta del cliente']))->toBeTrue();
})->with([
    TicketStatus::New,
    TicketStatus::Backlog,
    TicketStatus::Assigned,
    TicketStatus::Todo,
    TicketStatus::Progress,
]);

test('waiting declares the save-previous-status effect', function (): void {
    $transition = collect(TicketStateMachine::transitions())
        ->first(fn ($transition) => $transition->appliesTo(TicketStatus::Progress)
            && $transition->to === TicketStatus::Waiting);

    expect($transition->effects)->toContain(TransitionEffect::SavePreviousStatus);
});

// --- {new,backlog,assigned,todo,progress} -> problem (reason guard) --------------

test('problem requires a non-empty problem_reason', function (): void {
    $assignee = userWithPermissions(PermissionEnum::TicketUpdateAssigned);
    $t = ticket(['status' => TicketStatus::Progress, 'assignee_id' => $assignee->id]);

    expect(TicketStateMachine::can($t, TicketStatus::Problem, $assignee))->toBeFalse();

    expect(fn () => TicketStateMachine::authorize($t, TicketStatus::Problem, $assignee))
        ->toThrow(ValidationException::class, 'Il motivo del blocco è obbligatorio.');

    expect(TicketStateMachine::can($t, TicketStatus::Problem, $assignee, ['problem_reason' => 'Bloccato da dipendenza esterna']))->toBeTrue();
});

// --- waiting -> previous_status (dynamic target, system actor allowed) -----------

test('waiting restores to previous_status for admin, assignee and the system user', function (): void {
    $assignee = userWithPermissions(PermissionEnum::TicketUpdateAssigned);
    $admin = userWithPermissions(PermissionEnum::TicketTransitionAny);
    $system = systemUser();
    $t = ticket([
        'status' => TicketStatus::Waiting,
        'previous_status' => TicketStatus::Todo,
        'assignee_id' => $assignee->id,
        'waiting_reason' => 'In attesa di risposta del cliente',
    ]);

    expect(TicketStateMachine::can($t, TicketStatus::Todo, $assignee))->toBeTrue()
        ->and(TicketStateMachine::can($t, TicketStatus::Todo, $admin))->toBeTrue()
        ->and(TicketStateMachine::can($t, TicketStatus::Todo, $system))->toBeTrue();
});

test('requesting a target different from previous_status while waiting is not an admitted transition', function (): void {
    $admin = userWithPermissions(PermissionEnum::TicketTransitionAny);
    $t = ticket([
        'status' => TicketStatus::Waiting,
        'previous_status' => TicketStatus::Todo,
        'waiting_reason' => 'In attesa di risposta del cliente',
    ]);

    expect(TicketStateMachine::can($t, TicketStatus::Progress, $admin))->toBeFalse();

    expect(fn () => TicketStateMachine::authorize($t, TicketStatus::Progress, $admin))
        ->toThrow(ValidationException::class, 'non è ammessa');
});

test('waiting cannot restore without a previous_status set', function (): void {
    $admin = userWithPermissions(PermissionEnum::TicketTransitionAny);
    $t = ticket(['status' => TicketStatus::Waiting, 'waiting_reason' => 'x']);

    // With no previous_status, no target can ever match this transition's dynamic "to",
    // so the request is rejected as "not admitted" rather than as a failed guard.
    expect(TicketStateMachine::can($t, TicketStatus::Todo, $admin))->toBeFalse();
});

// --- problem -> previous_status (dynamic target, NO system actor) ---------------

test('problem restores to previous_status for admin and assignee but not for the system user', function (): void {
    $assignee = userWithPermissions(PermissionEnum::TicketUpdateAssigned);
    $system = systemUser();
    $t = ticket([
        'status' => TicketStatus::Problem,
        'previous_status' => TicketStatus::Progress,
        'assignee_id' => $assignee->id,
        'problem_reason' => 'Bloccato da dipendenza esterna',
    ]);

    expect(TicketStateMachine::can($t, TicketStatus::Progress, $assignee))->toBeTrue()
        ->and(TicketStateMachine::can($t, TicketStatus::Progress, $system))->toBeFalse();
});

// --- catch-all "* -> rejected" (admin/manager only, not the assignee) ------------

test('admin/manager can reject a ticket from any other status, but the assignee cannot', function (TicketStatus $from): void {
    $admin = userWithPermissions(PermissionEnum::TicketTransitionAny);
    $assignee = userWithPermissions(PermissionEnum::TicketUpdateAssigned);
    $t = ticket([
        'status' => $from,
        'assignee_id' => $assignee->id,
        'previous_status' => in_array($from, [TicketStatus::Waiting, TicketStatus::Problem], strict: true) ? TicketStatus::Todo : null,
        'waiting_reason' => $from === TicketStatus::Waiting ? 'x' : null,
        'problem_reason' => $from === TicketStatus::Problem ? 'x' : null,
    ]);

    expect(TicketStateMachine::can($t, TicketStatus::Rejected, $admin))->toBeTrue()
        ->and(TicketStateMachine::can($t, TicketStatus::Rejected, $assignee))->toBeFalse();
})->with([
    TicketStatus::Backlog,
    TicketStatus::Assigned,
    TicketStatus::Todo,
    TicketStatus::Progress,
    TicketStatus::Tested,
    TicketStatus::Released,
    TicketStatus::Waiting,
    TicketStatus::Problem,
]);

// --- transitions absent from the table --------------------------------------------

test('a transition absent from the table produces a localized validation error, never a generic exception', function (): void {
    $admin = userWithPermissions(PermissionEnum::TicketTransitionAny);
    $t = ticket(['status' => TicketStatus::Done]);

    expect(TicketStateMachine::can($t, TicketStatus::New, $admin))->toBeFalse();

    try {
        TicketStateMachine::authorize($t, TicketStatus::New, $admin);
        $this->fail('Expected a ValidationException to be thrown.');
    } catch (ValidationException $exception) {
        expect($exception->errors())->toHaveKey('status')
            ->and($exception->errors()['status'][0])->toContain('non è ammessa');
    }
});

test('done and rejected are terminal: no transition in the table starts from them', function (TicketStatus $terminal): void {
    $admin = userWithPermissions(PermissionEnum::TicketTransitionAny);
    $t = ticket(['status' => $terminal]);

    foreach (TicketStatus::cases() as $target) {
        expect(TicketStateMachine::can($t, $target, $admin))->toBeFalse();
    }
})->with([TicketStatus::Done, TicketStatus::Rejected]);
