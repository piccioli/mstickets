<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Ticketing\Enums\TicketMessageChannel;
use App\Domain\Ticketing\Enums\TicketMessageVisibility;
use App\Domain\Ticketing\Models\TicketMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeVisibilityScopedMessage(TicketMessageVisibility $visibility): TicketMessage
{
    return TicketMessage::create([
        'ticket_id' => ticket()->id,
        'channel' => TicketMessageChannel::Web,
        'visibility' => $visibility,
        'body_text' => 'Ciao',
        'posted_at' => now(),
    ]);
}

test('a customer without ticket-message.view.internal never sees an internal message via the scope', function (): void {
    $customer = userWithPermissions(PermissionEnum::TicketViewOwn);
    $public = makeVisibilityScopedMessage(TicketMessageVisibility::Public);
    $internal = makeVisibilityScopedMessage(TicketMessageVisibility::Internal);

    $visible = TicketMessage::query()->visibleTo($customer)->pluck('id');

    expect($visible)->toContain($public->id)->not->toContain($internal->id);
});

test('a customer cannot reach an internal message even via direct by-id access through the scope', function (): void {
    $customer = userWithPermissions(PermissionEnum::TicketViewOwn);
    $internal = makeVisibilityScopedMessage(TicketMessageVisibility::Internal);

    $found = TicketMessage::query()->visibleTo($customer)->find($internal->id);

    expect($found)->toBeNull();
});

test('a staff member with ticket-message.view.internal sees internal messages via the scope', function (): void {
    $staff = userWithPermissions(PermissionEnum::TicketMessageViewInternal);
    $internal = makeVisibilityScopedMessage(TicketMessageVisibility::Internal);

    $found = TicketMessage::query()->visibleTo($staff)->find($internal->id);

    expect($found?->id)->toBe($internal->id);
});
