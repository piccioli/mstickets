<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Ticketing\Enums\TicketMessageChannel;
use App\Domain\Ticketing\Enums\TicketMessageVisibility;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeTicketMessage(TicketMessageVisibility $visibility): TicketMessage
{
    $ticket = Ticket::create(['title' => 'Errore login', 'status_changed_at' => now()]);

    return TicketMessage::create([
        'ticket_id' => $ticket->id,
        'channel' => TicketMessageChannel::Web,
        'visibility' => $visibility,
        'body_text' => 'Ciao',
        'posted_at' => now(),
    ]);
}

test('a user without any ticket-message permission is denied every TicketMessagePolicy ability', function (): void {
    $actor = userWithPermissions();
    $public = makeTicketMessage(TicketMessageVisibility::Public);
    $internal = makeTicketMessage(TicketMessageVisibility::Internal);

    expect($actor->can('viewAny', TicketMessage::class))->toBeFalse()
        ->and($actor->can('view', $public))->toBeFalse()
        ->and($actor->can('view', $internal))->toBeFalse()
        ->and($actor->can('create', TicketMessage::class))->toBeFalse()
        ->and($actor->can('update', $public))->toBeFalse()
        ->and($actor->can('delete', $public))->toBeFalse();
});

test('a public ticket message is gated by ticket.view.*, an internal one by ticket-message.view.internal', function (): void {
    $public = makeTicketMessage(TicketMessageVisibility::Public);
    $internal = makeTicketMessage(TicketMessageVisibility::Internal);

    $ticketViewer = userWithPermissions(PermissionEnum::TicketViewAny);
    expect($ticketViewer->can('view', $public))->toBeTrue()
        ->and($ticketViewer->can('view', $internal))->toBeFalse();

    $internalViewer = userWithPermissions(PermissionEnum::TicketMessageViewInternal);
    expect($internalViewer->can('view', $internal))->toBeTrue()
        ->and($internalViewer->can('view', $public))->toBeFalse();
});

test('a user with ticket-message.create can create messages, but nobody can update or delete them', function (): void {
    $public = makeTicketMessage(TicketMessageVisibility::Public);

    expect(userWithPermissions(PermissionEnum::TicketMessageCreate)->can('create', TicketMessage::class))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::TicketMessageCreate)->can('update', $public))->toBeFalse()
        ->and(userWithPermissions(PermissionEnum::TicketMessageCreate)->can('delete', $public))->toBeFalse();
});
