<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Mail\Actions\BuildCustomerTicketDigest;
use App\Domain\Ticketing\Enums\TicketLogEvent;
use App\Domain\Ticketing\Enums\TicketMessageVisibility;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\TicketLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('includes a ticket with a new public message from staff in the last 24h', function (): void {
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $staff = User::factory()->create();
    $ticket = ticket(['requester_id' => $customer->id]);

    $this->travelTo(now()->subHours(2));
    ticketMessage(['ticket_id' => $ticket->id, 'author_id' => $staff->id, 'visibility' => TicketMessageVisibility::Public, 'posted_at' => now()]);
    $this->travelBack();

    $entries = BuildCustomerTicketDigest::run($customer, now()->subHours(24));

    expect($entries)->toHaveCount(1)
        ->and($entries->first()->ticket->is($ticket))->toBeTrue()
        ->and($entries->first()->newMessagesCount)->toBe(1);
});

test('excludes a message posted by the customer being digested', function (): void {
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $ticket = ticket(['requester_id' => $customer->id]);

    ticketMessage(['ticket_id' => $ticket->id, 'author_id' => $customer->id, 'visibility' => TicketMessageVisibility::Public, 'posted_at' => now()]);

    $entries = BuildCustomerTicketDigest::run($customer, now()->subHours(24));

    expect($entries)->toBeEmpty();
});

test('excludes an internal message', function (): void {
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $staff = User::factory()->create();
    $ticket = ticket(['requester_id' => $customer->id]);

    ticketMessage(['ticket_id' => $ticket->id, 'author_id' => $staff->id, 'visibility' => TicketMessageVisibility::Internal, 'posted_at' => now()]);

    $entries = BuildCustomerTicketDigest::run($customer, now()->subHours(24));

    expect($entries)->toBeEmpty();
});

test('excludes a message posted before the window', function (): void {
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $staff = User::factory()->create();
    $ticket = ticket(['requester_id' => $customer->id]);

    $this->travelTo(now()->subHours(30));
    ticketMessage(['ticket_id' => $ticket->id, 'author_id' => $staff->id, 'visibility' => TicketMessageVisibility::Public, 'posted_at' => now()]);
    $this->travelBack();

    $entries = BuildCustomerTicketDigest::run($customer, now()->subHours(24));

    expect($entries)->toBeEmpty();
});

test('includes a ticket with a status change in the last 24h, reporting from/to status', function (): void {
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $ticket = ticket(['requester_id' => $customer->id, 'status' => TicketStatus::Progress]);

    TicketLog::create([
        'ticket_id' => $ticket->id,
        'event' => TicketLogEvent::StatusChanged,
        'from_status' => TicketStatus::Todo,
        'to_status' => TicketStatus::Progress,
        'is_system' => false,
        'occurred_at' => now()->subHours(1),
    ]);

    $entries = BuildCustomerTicketDigest::run($customer, now()->subHours(24));

    expect($entries)->toHaveCount(1);
    $entry = $entries->first();
    expect($entry->hasStatusChange())->toBeTrue()
        ->and($entry->previousStatus)->toBe(TicketStatus::Todo)
        ->and($entry->currentStatus)->toBe(TicketStatus::Progress);
});

test('aggregates several tickets with activity for the same customer', function (): void {
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $staff = User::factory()->create();

    $ticketA = ticket(['requester_id' => $customer->id, 'title' => 'Ticket A']);
    $ticketB = ticket(['requester_id' => $customer->id, 'title' => 'Ticket B', 'status' => TicketStatus::Done]);
    ticket(['requester_id' => $customer->id, 'title' => 'Ticket C - no activity']);

    ticketMessage(['ticket_id' => $ticketA->id, 'author_id' => $staff->id, 'visibility' => TicketMessageVisibility::Public, 'posted_at' => now()]);
    TicketLog::create([
        'ticket_id' => $ticketB->id,
        'event' => TicketLogEvent::StatusChanged,
        'from_status' => TicketStatus::Released,
        'to_status' => TicketStatus::Done,
        'is_system' => false,
        'occurred_at' => now(),
    ]);

    $entries = BuildCustomerTicketDigest::run($customer, now()->subHours(24));

    $titles = $entries->map(fn ($entry): string => $entry->ticket->title)->all();

    expect($entries)->toHaveCount(2)
        ->and($titles)->toContain('Ticket A', 'Ticket B')
        ->and($titles)->not->toContain('Ticket C - no activity');
});

test('ignores tickets belonging to another customer', function (): void {
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $otherCustomer = withRole(User::factory()->create(), UserRole::Customer);
    $staff = User::factory()->create();

    $otherTicket = ticket(['requester_id' => $otherCustomer->id]);
    ticketMessage(['ticket_id' => $otherTicket->id, 'author_id' => $staff->id, 'visibility' => TicketMessageVisibility::Public, 'posted_at' => now()]);

    $entries = BuildCustomerTicketDigest::run($customer, now()->subHours(24));

    expect($entries)->toBeEmpty();
});
