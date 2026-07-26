<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketParticipant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a user without ticket permissions is denied every TicketParticipantPolicy ability', function (): void {
    $actor = userWithPermissions();
    $ticket = Ticket::create(['title' => 'Errore login', 'status_changed_at' => now()]);
    $participant = TicketParticipant::create(['ticket_id' => $ticket->id, 'user_id' => User::factory()->create()->id]);

    expect($actor->can('viewAny', TicketParticipant::class))->toBeFalse()
        ->and($actor->can('view', $participant))->toBeFalse()
        ->and($actor->can('create', TicketParticipant::class))->toBeFalse()
        ->and($actor->can('update', $participant))->toBeFalse()
        ->and($actor->can('delete', $participant))->toBeFalse();
});

test('viewing participants is gated by ticket.view.*, managing them by ticket.assign', function (): void {
    $ticket = Ticket::create(['title' => 'Errore login', 'status_changed_at' => now()]);
    $participant = TicketParticipant::create(['ticket_id' => $ticket->id, 'user_id' => User::factory()->create()->id]);

    $viewer = userWithPermissions(PermissionEnum::TicketViewAny);
    expect($viewer->can('view', $participant))->toBeTrue()
        ->and($viewer->can('create', TicketParticipant::class))->toBeFalse();

    $assigner = userWithPermissions(PermissionEnum::TicketAssign);
    expect($assigner->can('create', TicketParticipant::class))->toBeTrue()
        ->and($assigner->can('update', $participant))->toBeTrue()
        ->and($assigner->can('delete', $participant))->toBeTrue();
});
