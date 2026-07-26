<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketWorkLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a user without any ticket permission is denied every TicketWorkLogPolicy ability', function (): void {
    $actor = userWithPermissions();
    $ticket = Ticket::create(['title' => 'Errore login', 'status_changed_at' => now()]);
    $workLog = TicketWorkLog::create([
        'work_date' => now()->toDateString(),
        'user_id' => User::factory()->create()->id,
        'ticket_id' => $ticket->id,
        'minutes' => 30,
    ]);

    expect($actor->can('viewAny', TicketWorkLog::class))->toBeFalse()
        ->and($actor->can('view', $workLog))->toBeFalse()
        ->and($actor->can('create', TicketWorkLog::class))->toBeFalse()
        ->and($actor->can('update', $workLog))->toBeFalse()
        ->and($actor->can('delete', $workLog))->toBeFalse();
});

test('viewing work logs is gated by ticket.view.*, logging hours by ticket.update.*', function (): void {
    $ticket = Ticket::create(['title' => 'Errore login', 'status_changed_at' => now()]);
    $workLog = TicketWorkLog::create([
        'work_date' => now()->toDateString(),
        'user_id' => User::factory()->create()->id,
        'ticket_id' => $ticket->id,
        'minutes' => 30,
    ]);

    $viewer = userWithPermissions(PermissionEnum::TicketViewOwn);
    expect($viewer->can('view', $workLog))->toBeTrue()
        ->and($viewer->can('create', TicketWorkLog::class))->toBeFalse();

    $updater = userWithPermissions(PermissionEnum::TicketUpdateOwn);
    expect($updater->can('create', TicketWorkLog::class))->toBeTrue()
        ->and($updater->can('update', $workLog))->toBeTrue()
        ->and($updater->can('delete', $workLog))->toBeTrue();
});
