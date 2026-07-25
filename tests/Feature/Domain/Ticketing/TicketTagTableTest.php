<?php

declare(strict_types=1);

use App\Domain\Tags\Models\Tag;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('ticket_tag table has the columns required by §5.2', function (): void {
    expect(Schema::hasColumns('ticket_tag', ['id', 'ticket_id', 'tag_id', 'created_at', 'updated_at']))
        ->toBeTrue();
});

test('the ticket/tag pair is unique', function (): void {
    $ticket = Ticket::create(['title' => 'Ticket taggato', 'status_changed_at' => now()]);
    $tag = Tag::create(['name' => 'Supporto', 'slug' => 'supporto']);

    DB::table('ticket_tag')->insert(['ticket_id' => $ticket->id, 'tag_id' => $tag->id, 'created_at' => now(), 'updated_at' => now()]);

    expect(fn () => DB::table('ticket_tag')->insert([
        'ticket_id' => $ticket->id, 'tag_id' => $tag->id, 'created_at' => now(), 'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});
