<?php

declare(strict_types=1);

use App\Domain\Tags\Models\Tag;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('sal() is null when estimated_hours is null', function (): void {
    $tag = Tag::create(['name' => 'Manutenzione', 'slug' => 'manutenzione']);

    expect($tag->sal())->toBeNull();
});

test('sal() is null when estimated_hours is zero', function (): void {
    $tag = Tag::create(['name' => 'Manutenzione', 'slug' => 'manutenzione', 'estimated_hours' => 0]);

    expect($tag->sal())->toBeNull();
});

test('workedMinutes() is zero when no ticket is linked', function (): void {
    $tag = Tag::create(['name' => 'Manutenzione', 'slug' => 'manutenzione', 'estimated_hours' => 10]);

    expect($tag->workedMinutes())->toBe(0)
        ->and($tag->sal())->toBe(0.0);
});

test('workedMinutes() sums worked_minutes across all linked tickets', function (): void {
    $tag = Tag::create(['name' => 'Manutenzione', 'slug' => 'manutenzione', 'estimated_hours' => 10]);
    $ticketA = Ticket::create(['title' => 'A', 'status_changed_at' => now(), 'worked_minutes' => 90]);
    $ticketB = Ticket::create(['title' => 'B', 'status_changed_at' => now(), 'worked_minutes' => 30]);
    $tag->tickets()->attach([$ticketA->id, $ticketB->id]);

    expect($tag->workedMinutes())->toBe(120);
});

test('sal() rounds to two decimal places', function (): void {
    $tag = Tag::create(['name' => 'Manutenzione', 'slug' => 'manutenzione', 'estimated_hours' => 3]);
    $ticket = Ticket::create(['title' => 'A', 'status_changed_at' => now(), 'worked_minutes' => 100]);
    $tag->tickets()->attach($ticket);

    // worked hours = 100/60 = 1.6666..., sal = 1.6666../3 * 100 = 55.5555...
    expect($tag->sal())->toBe(55.56);
});

test('isClosed() is false when the tag has no linked tickets', function (): void {
    $tag = Tag::create(['name' => 'Manutenzione', 'slug' => 'manutenzione']);

    expect($tag->isClosed())->toBeFalse();
});

test('isClosed() is false when at least one linked ticket is not released or done', function (): void {
    $tag = Tag::create(['name' => 'Manutenzione', 'slug' => 'manutenzione']);
    $done = Ticket::create(['title' => 'A', 'status' => TicketStatus::Done, 'status_changed_at' => now()]);
    $progress = Ticket::create(['title' => 'B', 'status' => TicketStatus::Progress, 'status_changed_at' => now()]);
    $tag->tickets()->attach([$done->id, $progress->id]);

    expect($tag->isClosed())->toBeFalse();
});

test('isClosed() is true when every linked ticket is released or done', function (): void {
    $tag = Tag::create(['name' => 'Manutenzione', 'slug' => 'manutenzione']);
    $done = Ticket::create(['title' => 'A', 'status' => TicketStatus::Done, 'status_changed_at' => now()]);
    $released = Ticket::create(['title' => 'B', 'status' => TicketStatus::Released, 'status_changed_at' => now()]);
    $tag->tickets()->attach([$done->id, $released->id]);

    expect($tag->isClosed())->toBeTrue();
});
