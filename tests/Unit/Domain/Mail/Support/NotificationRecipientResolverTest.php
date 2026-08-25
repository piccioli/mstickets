<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Mail\Support\NotificationRecipientResolver;
use App\Domain\Ticketing\Enums\TicketStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a transition with no row in the table resolves to nobody', function (): void {
    $actor = withRole(User::factory()->create(), UserRole::Developer);
    $ticket = ticket(['assignee_id' => $actor->id]);

    $recipients = NotificationRecipientResolver::resolve($ticket, TicketStatus::Todo, TicketStatus::Progress, $actor);

    expect($recipients)->toBeEmpty();
});

test('new to rejected resolves to the requester only', function (): void {
    $requester = withRole(User::factory()->create(), UserRole::Customer);
    $actor = withRole(User::factory()->create(), UserRole::Manager);
    $ticket = ticket(['requester_id' => $requester->id]);

    $recipients = NotificationRecipientResolver::resolve($ticket, TicketStatus::New, TicketStatus::Rejected, $actor);

    expect($recipients->pluck('id')->all())->toBe([$requester->id]);
});

test('the requester is excluded when they are also the actor', function (): void {
    $requester = withRole(User::factory()->create(), UserRole::Customer);
    $ticket = ticket(['requester_id' => $requester->id]);

    $recipients = NotificationRecipientResolver::resolve($ticket, TicketStatus::New, TicketStatus::Rejected, $requester);

    expect($recipients)->toBeEmpty();
});

test('testing to rejected resolves to both the assignee and the requester, more specific than the catch-all', function (): void {
    $requester = withRole(User::factory()->create(), UserRole::Customer);
    $assignee = withRole(User::factory()->create(), UserRole::Developer);
    $actor = withRole(User::factory()->create(), UserRole::Manager);
    $ticket = ticket(['requester_id' => $requester->id, 'assignee_id' => $assignee->id]);

    $recipients = NotificationRecipientResolver::resolve($ticket, TicketStatus::Testing, TicketStatus::Rejected, $actor);

    expect($recipients->pluck('id')->sort()->values()->all())->toBe(collect([$requester->id, $assignee->id])->sort()->values()->all());
});

test('the assignee is excluded from testing to rejected when they are also the actor', function (): void {
    $requester = withRole(User::factory()->create(), UserRole::Customer);
    $assignee = withRole(User::factory()->create(), UserRole::Developer);
    $ticket = ticket(['requester_id' => $requester->id, 'assignee_id' => $assignee->id]);

    $recipients = NotificationRecipientResolver::resolve($ticket, TicketStatus::Testing, TicketStatus::Rejected, $assignee);

    expect($recipients->pluck('id')->all())->toBe([$requester->id]);
});

test('an unrelated status resolves to the catch-all requester rule', function (): void {
    $requester = withRole(User::factory()->create(), UserRole::Customer);
    $actor = withRole(User::factory()->create(), UserRole::Manager);
    $ticket = ticket(['requester_id' => $requester->id, 'status' => TicketStatus::Released]);

    $recipients = NotificationRecipientResolver::resolve($ticket, TicketStatus::Released, TicketStatus::Rejected, $actor);

    expect($recipients->pluck('id')->all())->toBe([$requester->id]);
});

test('progress to testing resolves to the tester only', function (): void {
    $tester = withRole(User::factory()->create(), UserRole::Developer);
    $actor = withRole(User::factory()->create(), UserRole::Developer);
    $ticket = ticket(['tester_id' => $tester->id]);

    $recipients = NotificationRecipientResolver::resolve($ticket, TicketStatus::Progress, TicketStatus::Testing, $actor);

    expect($recipients->pluck('id')->all())->toBe([$tester->id]);
});

test('problem resolves to every active manager, excluding the actor if they are a manager', function (): void {
    $actingManager = withRole(User::factory()->create(), UserRole::Manager);
    $otherManager = withRole(User::factory()->create(), UserRole::Manager);
    withRole(User::factory()->create(['deactivated_at' => now()]), UserRole::Manager);
    withRole(User::factory()->create(), UserRole::Developer);
    $ticket = ticket();

    $recipients = NotificationRecipientResolver::resolve($ticket, TicketStatus::Progress, TicketStatus::Problem, $actingManager);

    expect($recipients->pluck('id')->all())->toBe([$otherManager->id]);
});

test('a role with no matching user on the ticket resolves to nobody for that role', function (): void {
    $actor = withRole(User::factory()->create(), UserRole::Manager);
    $ticket = ticket();

    $recipients = NotificationRecipientResolver::resolve($ticket, TicketStatus::New, TicketStatus::Rejected, $actor);

    expect($recipients)->toBeEmpty();
});
