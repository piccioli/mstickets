<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketView;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a user without any ticket view permission is denied every TicketViewPolicy ability', function (): void {
    $actor = userWithPermissions();
    $ticket = Ticket::create(['title' => 'Errore login', 'status_changed_at' => now()]);
    $view = TicketView::create([
        'ticket_id' => $ticket->id,
        'user_id' => User::factory()->create()->id,
        'viewed_on' => now()->toDateString(),
        'last_viewed_at' => now(),
    ]);

    expect($actor->can('viewAny', TicketView::class))->toBeFalse()
        ->and($actor->can('view', $view))->toBeFalse()
        ->and($actor->can('create', TicketView::class))->toBeFalse()
        ->and($actor->can('update', $view))->toBeFalse()
        ->and($actor->can('delete', $view))->toBeFalse();
});

test('a user who can view tickets can also read/write their own view markers', function (): void {
    $actor = userWithPermissions(PermissionEnum::TicketViewOwn);
    $ticket = Ticket::create(['title' => 'Errore login', 'status_changed_at' => now()]);
    $view = TicketView::create([
        'ticket_id' => $ticket->id,
        'user_id' => User::factory()->create()->id,
        'viewed_on' => now()->toDateString(),
        'last_viewed_at' => now(),
    ]);

    expect($actor->can('viewAny', TicketView::class))->toBeTrue()
        ->and($actor->can('view', $view))->toBeTrue()
        ->and($actor->can('create', TicketView::class))->toBeTrue()
        ->and($actor->can('update', $view))->toBeTrue()
        ->and($actor->can('delete', $view))->toBeTrue();
});
