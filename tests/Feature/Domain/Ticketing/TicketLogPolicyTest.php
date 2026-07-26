<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Ticketing\Enums\TicketLogEvent;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a user without ticket-log.view is denied every TicketLogPolicy ability', function (): void {
    $actor = userWithPermissions();
    $ticket = Ticket::create(['title' => 'Errore login', 'status_changed_at' => now()]);
    $log = TicketLog::create([
        'ticket_id' => $ticket->id,
        'event' => TicketLogEvent::Created,
        'is_system' => true,
        'occurred_at' => now(),
    ]);

    expect($actor->can('viewAny', TicketLog::class))->toBeFalse()
        ->and($actor->can('view', $log))->toBeFalse()
        ->and($actor->can('create', TicketLog::class))->toBeFalse()
        ->and($actor->can('update', $log))->toBeFalse()
        ->and($actor->can('delete', $log))->toBeFalse();
});

test('a user with ticket-log.view can only view logs, never write them (system-only writes)', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketLogView);
    $ticket = Ticket::create(['title' => 'Errore login', 'status_changed_at' => now()]);
    $log = TicketLog::create([
        'ticket_id' => $ticket->id,
        'event' => TicketLogEvent::Created,
        'is_system' => true,
        'occurred_at' => now(),
    ]);

    expect($actor->can('viewAny', TicketLog::class))->toBeTrue()
        ->and($actor->can('view', $log))->toBeTrue()
        ->and($actor->can('create', TicketLog::class))->toBeFalse()
        ->and($actor->can('update', $log))->toBeFalse()
        ->and($actor->can('delete', $log))->toBeFalse();
});
