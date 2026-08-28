<?php

declare(strict_types=1);

use App\Domain\Ticketing\Enums\TicketLogEvent;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Enums\TicketType;
use App\Domain\Ticketing\Models\TicketLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->travelTo('2026-03-31 05:00:00');
});

test('--dry-run examines archivable scrum tickets without archiving any of them', function (): void {
    $ticket = ticket([
        'type' => TicketType::Scrum,
        'status' => TicketStatus::Done,
        'done_at' => '2026-02-01 10:00:00',
    ]);

    $this->artisan('tickets:archive-scrum', ['--dry-run' => true])->assertSuccessful();

    expect($ticket->fresh()->archived_at)->toBeNull();
    expect(TicketLog::count())->toBe(0);
});

test('archives a scrum ticket done for at least the configured threshold of days and logs it', function (): void {
    $ticket = ticket([
        'type' => TicketType::Scrum,
        'status' => TicketStatus::Done,
        'done_at' => '2026-02-01 10:00:00',
    ]);

    $this->artisan('tickets:archive-scrum')->assertSuccessful();

    $fresh = $ticket->fresh();
    expect($fresh->archived_at)->not->toBeNull()
        ->and($fresh->status)->toBe(TicketStatus::Done);

    $log = TicketLog::query()->where('ticket_id', $ticket->id)->sole();
    expect($log->is_system)->toBeTrue()
        ->and($log->event)->toBe(TicketLogEvent::Archived);
});

test('does not archive a scrum ticket done more recently than the threshold', function (): void {
    $ticket = ticket([
        'type' => TicketType::Scrum,
        'status' => TicketStatus::Done,
        'done_at' => '2026-03-20 10:00:00',
    ]);

    $this->artisan('tickets:archive-scrum')->assertSuccessful();

    expect($ticket->fresh()->archived_at)->toBeNull();
    expect(TicketLog::count())->toBe(0);
});

test('does not archive a scrum ticket that is not done', function (): void {
    $ticket = ticket([
        'type' => TicketType::Scrum,
        'status' => TicketStatus::Todo,
    ]);

    $this->artisan('tickets:archive-scrum')->assertSuccessful();

    expect($ticket->fresh()->archived_at)->toBeNull();
});

test('does not archive a non-scrum ticket done long ago', function (): void {
    $ticket = ticket([
        'type' => TicketType::Bug,
        'status' => TicketStatus::Done,
        'done_at' => '2026-02-01 10:00:00',
    ]);

    $this->artisan('tickets:archive-scrum')->assertSuccessful();

    expect($ticket->fresh()->archived_at)->toBeNull();
});

test('re-running the command is idempotent: an already archived ticket is not archived again', function (): void {
    $ticket = ticket([
        'type' => TicketType::Scrum,
        'status' => TicketStatus::Done,
        'done_at' => '2026-02-01 10:00:00',
    ]);

    $this->artisan('tickets:archive-scrum')->assertSuccessful();
    $this->artisan('tickets:archive-scrum')->assertSuccessful();

    expect(TicketLog::query()->where('ticket_id', $ticket->id)->count())->toBe(1);
});
