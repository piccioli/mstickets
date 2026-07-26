<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Actions\RecordTicketView;
use App\Domain\Ticketing\Models\TicketLog;
use App\Domain\Ticketing\Models\TicketView;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the first view of the day creates a ticket_view row', function (): void {
    $this->travelTo(now()->setTime(10, 0));

    $ticket = ticket();
    $user = User::factory()->create();

    $ticketView = RecordTicketView::run($ticket, $user);

    expect(TicketView::query()->count())->toBe(1)
        ->and($ticketView->ticket_id)->toBe($ticket->id)
        ->and($ticketView->user_id)->toBe($user->id)
        ->and($ticketView->viewed_on->toDateString())->toBe(now()->toDateString())
        ->and($ticketView->view_count)->toBe(1)
        ->and($ticketView->last_viewed_at->equalTo(now()))->toBeTrue();
});

test('a second view within the throttle window does not touch last_viewed_at/view_count', function (): void {
    $this->travelTo(now()->setTime(10, 0));

    $ticket = ticket();
    $user = User::factory()->create();

    $first = RecordTicketView::run($ticket, $user);

    $this->travel(10)->minutes();
    $second = RecordTicketView::run($ticket, $user);

    expect(TicketView::query()->count())->toBe(1)
        ->and($second->id)->toBe($first->id)
        ->and($second->view_count)->toBe(1)
        ->and($second->last_viewed_at->equalTo($first->last_viewed_at))->toBeTrue();
});

test('a view beyond the throttle window updates last_viewed_at and increments view_count', function (): void {
    $this->travelTo(now()->setTime(10, 0));

    $ticket = ticket();
    $user = User::factory()->create();

    $first = RecordTicketView::run($ticket, $user);

    $this->travel(31)->minutes();
    $second = RecordTicketView::run($ticket, $user);

    expect(TicketView::query()->count())->toBe(1)
        ->and($second->id)->toBe($first->id)
        ->and($second->view_count)->toBe(2)
        ->and($second->last_viewed_at->equalTo($first->last_viewed_at))->toBeFalse()
        ->and($second->last_viewed_at->equalTo(now()))->toBeTrue();
});

test('views on different days each create their own row', function (): void {
    $this->travelTo(now()->setTime(10, 0));

    $ticket = ticket();
    $user = User::factory()->create();

    RecordTicketView::run($ticket, $user);

    $this->travel(1)->days();
    RecordTicketView::run($ticket, $user);

    expect(TicketView::query()->count())->toBe(2);
});

test('recording a view never writes to ticket_logs', function (): void {
    $ticket = ticket();
    $user = User::factory()->create();

    RecordTicketView::run($ticket, $user);
    RecordTicketView::run($ticket, $user);

    expect(TicketLog::query()->count())->toBe(0);
});
