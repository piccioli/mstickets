<?php

declare(strict_types=1);

use App\Domain\Tags\Actions\CreateTagFromTicket;
use App\Domain\Tags\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('creating a tag from a ticket precompiles estimated_hours from the ticket and links it', function (): void {
    $ticketRecord = ticket(['title' => 'Portale prenotazioni', 'estimated_hours' => 12.5]);

    $tag = CreateTagFromTicket::run($ticketRecord, 'Portale prenotazioni');

    expect($tag->name)->toBe('Portale prenotazioni')
        ->and($tag->slug)->toBe('portale-prenotazioni')
        ->and((float) $tag->estimated_hours)->toBe(12.5)
        ->and($tag->tickets()->pluck('tickets.id')->all())->toBe([$ticketRecord->id]);
});

test('an explicit estimated_hours overrides the ticket value', function (): void {
    $ticketRecord = ticket(['estimated_hours' => 12.5]);

    $tag = CreateTagFromTicket::run($ticketRecord, 'Commessa personalizzata', 40.0);

    expect((float) $tag->estimated_hours)->toBe(40.0);
});

test('estimated_hours is null when the ticket has none and none is given', function (): void {
    $ticketRecord = ticket(['estimated_hours' => null]);

    $tag = CreateTagFromTicket::run($ticketRecord, 'Commessa senza stima');

    expect($tag->estimated_hours)->toBeNull();
});

test('the generated slug gets a numeric suffix when it collides with an existing tag, including soft-deleted ones', function (): void {
    tag(['name' => 'Duplicato', 'slug' => 'duplicato']);
    tag(['name' => 'Duplicato 2', 'slug' => 'duplicato-2'])->delete();

    $tag = CreateTagFromTicket::run(ticket(), 'Duplicato');

    expect($tag->slug)->toBe('duplicato-3');
});

test('the ticket is not duplicated in the pivot if it is already linked to the tag', function (): void {
    $ticketRecord = ticket();
    $tag = CreateTagFromTicket::run($ticketRecord, 'Commessa');

    $tag->tickets()->syncWithoutDetaching([$ticketRecord->id]);

    expect($tag->tickets()->count())->toBe(1);

    $rows = DB::table('ticket_tag')
        ->where('ticket_id', $ticketRecord->id)
        ->where('tag_id', $tag->id)
        ->count();

    expect($rows)->toBe(1);
});

test('a ticket can be linked to more than one tag and a tag to more than one ticket', function (): void {
    $ticketRecord = ticket();
    $otherTicket = ticket();

    $firstTag = CreateTagFromTicket::run($ticketRecord, 'Prima commessa');
    $secondTag = CreateTagFromTicket::run($ticketRecord, 'Seconda commessa');
    $firstTag->tickets()->syncWithoutDetaching([$otherTicket->id]);

    expect($ticketRecord->tags()->count())->toBe(2)
        ->and($firstTag->tickets()->count())->toBe(2)
        ->and(Tag::query()->count())->toBe(2);
});
