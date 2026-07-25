<?php

declare(strict_types=1);

use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * `tags` non esiste ancora (arriva in US-013): niente Model/relazione Eloquent per questa
 * tabella pivot in questa story, solo lo schema verificato via query builder. La FK su
 * `tag_id` va aggiunta quando US-013 introduce la tabella `tags`.
 */
test('ticket_tag table has the columns required by §5.2', function (): void {
    expect(Schema::hasColumns('ticket_tag', ['id', 'ticket_id', 'tag_id', 'created_at', 'updated_at']))
        ->toBeTrue();
});

test('the ticket/tag pair is unique', function (): void {
    $ticket = Ticket::create(['title' => 'Ticket taggato', 'status_changed_at' => now()]);

    DB::table('ticket_tag')->insert(['ticket_id' => $ticket->id, 'tag_id' => 1, 'created_at' => now(), 'updated_at' => now()]);

    expect(fn () => DB::table('ticket_tag')->insert([
        'ticket_id' => $ticket->id, 'tag_id' => 1, 'created_at' => now(), 'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});
