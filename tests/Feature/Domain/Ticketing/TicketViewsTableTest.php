<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketView;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('ticket_views table has the columns required by §6.2.3', function (): void {
    expect(Schema::hasColumns('ticket_views', [
        'id', 'ticket_id', 'user_id', 'viewed_on', 'last_viewed_at', 'view_count', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

test('the ticket/user/viewed_on triple is unique', function (): void {
    $ticket = Ticket::create(['title' => 'Ticket visto', 'status_changed_at' => now()]);
    $user = User::factory()->create();
    $viewedOn = now()->toDateString();

    TicketView::create([
        'ticket_id' => $ticket->id, 'user_id' => $user->id, 'viewed_on' => $viewedOn, 'last_viewed_at' => now(),
    ]);

    expect(fn () => TicketView::create([
        'ticket_id' => $ticket->id, 'user_id' => $user->id, 'viewed_on' => $viewedOn, 'last_viewed_at' => now(),
    ]))->toThrow(QueryException::class);
});
