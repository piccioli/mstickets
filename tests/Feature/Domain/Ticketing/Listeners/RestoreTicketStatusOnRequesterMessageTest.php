<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Actions\PostTicketMessage;
use App\Domain\Ticketing\Enums\TicketLogEvent;
use App\Domain\Ticketing\Enums\TicketStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a requester message on a waiting ticket restores it to previous_status, attributed to the system user', function (): void {
    $requester = User::factory()->create();
    $t = ticket([
        'requester_id' => $requester->id,
        'status' => TicketStatus::Waiting,
        'previous_status' => TicketStatus::Progress,
        'waiting_reason' => 'In attesa di risposta del cliente',
    ]);

    PostTicketMessage::run($t, $requester, '<p>Ecco la risposta</p>');

    $updated = $t->fresh();

    expect($updated->status)->toBe(TicketStatus::Progress)
        ->and($updated->previous_status)->toBeNull();

    $statusLog = $updated->logs()->where('event', TicketLogEvent::StatusChanged)->sole();
    expect($statusLog->is_system)->toBeTrue()
        ->and($statusLog->from_status)->toBe(TicketStatus::Waiting)
        ->and($statusLog->to_status)->toBe(TicketStatus::Progress);
});

test('a requester message on an assigned or in-progress ticket moves it to todo', function (TicketStatus $from): void {
    $requester = User::factory()->create();
    $assignee = User::factory()->create();
    $t = ticket([
        'requester_id' => $requester->id,
        'assignee_id' => $assignee->id,
        'status' => $from,
    ]);

    PostTicketMessage::run($t, $requester, '<p>Ecco la risposta</p>');

    expect($t->fresh()->status)->toBe(TicketStatus::Todo);
})->with([TicketStatus::Assigned, TicketStatus::Progress]);

test('a requester message does not change the status if the ticket is already todo', function (): void {
    $requester = User::factory()->create();
    $t = ticket(['requester_id' => $requester->id, 'status' => TicketStatus::Todo]);

    PostTicketMessage::run($t, $requester, '<p>Ecco la risposta</p>');

    expect($t->fresh()->status)->toBe(TicketStatus::Todo)
        ->and($t->fresh()->logs()->where('event', TicketLogEvent::StatusChanged)->count())->toBe(0);
});

test('a message from someone other than the requester never triggers the T7 side effect', function (): void {
    $requester = User::factory()->create();
    $assignee = User::factory()->create();
    $t = ticket([
        'requester_id' => $requester->id,
        'assignee_id' => $assignee->id,
        'status' => TicketStatus::Assigned,
    ]);

    PostTicketMessage::run($t, $assignee, '<p>Aggiornamento</p>');

    expect($t->fresh()->status)->toBe(TicketStatus::Assigned)
        ->and($t->fresh()->logs()->where('event', TicketLogEvent::StatusChanged)->count())->toBe(0);
});

test('a requester message on a ticket in a status unrelated to T7 leaves the status untouched', function (): void {
    $requester = User::factory()->create();
    $t = ticket(['requester_id' => $requester->id, 'status' => TicketStatus::Backlog]);

    PostTicketMessage::run($t, $requester, '<p>Ciao</p>');

    expect($t->fresh()->status)->toBe(TicketStatus::Backlog);
});
