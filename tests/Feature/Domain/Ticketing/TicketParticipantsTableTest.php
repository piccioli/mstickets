<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('ticket_participants table has the columns required by §5.2', function (): void {
    expect(Schema::hasColumns('ticket_participants', ['id', 'ticket_id', 'user_id', 'created_at', 'updated_at']))
        ->toBeTrue();
});

test('a ticket tracks its participants and the pair is unique', function (): void {
    $ticket = Ticket::create(['title' => 'Ticket con partecipanti', 'status_changed_at' => now()]);
    $user = User::factory()->create();

    $ticket->participants()->attach($user);

    expect($ticket->participants()->count())->toBe(1)
        ->and(fn () => $ticket->participants()->attach($user))->toThrow(QueryException::class);
});
