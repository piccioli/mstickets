<?php

declare(strict_types=1);

use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Enums\TicketType;
use App\Domain\Ticketing\Models\TicketLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->travelTo('2026-03-10 16:00:00');
});

test('--dry-run examines scrum tickets created today without closing any of them', function (): void {
    $ticket = ticket(['type' => TicketType::Scrum, 'status' => TicketStatus::Todo]);

    $this->artisan('tickets:close-scrum', ['--dry-run' => true])->assertSuccessful();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Todo);
    expect(TicketLog::count())->toBe(0);
});

test('closes a scrum ticket created today and stamps done_at', function (): void {
    $ticket = ticket(['type' => TicketType::Scrum, 'status' => TicketStatus::Todo]);

    $this->artisan('tickets:close-scrum')->assertSuccessful();

    $fresh = $ticket->fresh();
    expect($fresh->status)->toBe(TicketStatus::Done)
        ->and($fresh->done_at)->not->toBeNull();

    $log = TicketLog::query()->where('ticket_id', $ticket->id)->sole();
    expect($log->is_system)->toBeTrue()
        ->and($log->from_status)->toBe(TicketStatus::Todo)
        ->and($log->to_status)->toBe(TicketStatus::Done);
});

test('closes a scrum ticket updated today even if created earlier', function (): void {
    $ticket = ticket(['type' => TicketType::Scrum, 'status' => TicketStatus::Todo]);
    $ticket->timestamps = false;
    $ticket->forceFill(['created_at' => '2026-03-01 09:00:00', 'updated_at' => '2026-03-10 08:00:00'])->save();

    $this->artisan('tickets:close-scrum')->assertSuccessful();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Done);
});

test('does not touch a scrum ticket neither created nor updated today', function (): void {
    $ticket = ticket(['type' => TicketType::Scrum, 'status' => TicketStatus::Todo]);
    $ticket->timestamps = false;
    $ticket->forceFill(['created_at' => '2026-03-01 09:00:00', 'updated_at' => '2026-03-01 09:00:00'])->save();

    $this->artisan('tickets:close-scrum')->assertSuccessful();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Todo);
    expect(TicketLog::count())->toBe(0);
});

test('does not touch a non-scrum ticket created today', function (): void {
    $ticket = ticket(['type' => TicketType::Bug, 'status' => TicketStatus::Todo]);

    $this->artisan('tickets:close-scrum')->assertSuccessful();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Todo);
});

test('re-running the command is idempotent: a scrum ticket already done is not transitioned again', function (): void {
    $ticket = ticket(['type' => TicketType::Scrum, 'status' => TicketStatus::Todo]);

    $this->artisan('tickets:close-scrum')->assertSuccessful();
    $this->artisan('tickets:close-scrum')->assertSuccessful();

    expect($ticket->fresh()->status)->toBe(TicketStatus::Done);
    expect(TicketLog::query()->where('ticket_id', $ticket->id)->count())->toBe(1);
});
