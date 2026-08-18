<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Actions\CreateTicket;
use App\Domain\Ticketing\Enums\TicketLogEvent;
use App\Domain\Ticketing\Enums\TicketMessageChannel;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Events\TicketCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('creates a ticket in new status regardless of the status attribute passed in', function (): void {
    $author = User::factory()->create();

    $created = CreateTicket::run([
        'title' => 'Errore login',
        'status' => TicketStatus::Done,
    ], $author);

    expect($created->status)->toBe(TicketStatus::New)
        ->and($created->title)->toBe('Errore login');
});

test('writes a created ticket_log with the acting user, never a hard-coded id', function (): void {
    $author = User::factory()->create();

    $created = CreateTicket::run(['title' => 'Errore login'], $author);

    $log = $created->logs()->sole();

    expect($log->event)->toBe(TicketLogEvent::Created)
        ->and($log->user_id)->toBe($author->id)
        ->and($log->is_system)->toBeFalse();
});

test('creating a ticket as the system user marks the log as is_system', function (): void {
    $system = systemUser();

    $created = CreateTicket::run(['title' => 'Ticket automatico'], $system);

    expect($created->logs()->sole()->is_system)->toBeTrue();
});

test('emits TicketCreated', function (): void {
    Event::fake();

    $author = User::factory()->create();

    $created = CreateTicket::run(['title' => 'Errore login'], $author);

    Event::assertDispatched(TicketCreated::class, fn (TicketCreated $event): bool => $event->ticket->is($created));
});

test('emits TicketCreated with the web channel by default', function (): void {
    Event::fake();

    $author = User::factory()->create();

    CreateTicket::run(['title' => 'Errore login'], $author);

    Event::assertDispatched(TicketCreated::class, fn (TicketCreated $event): bool => $event->channel === TicketMessageChannel::Web);
});

test('emits TicketCreated with the given channel when the caller specifies one explicitly (US-311, pipeline email)', function (): void {
    Event::fake();

    $author = User::factory()->create();

    CreateTicket::run(['title' => 'Errore login'], $author, TicketMessageChannel::Email);

    Event::assertDispatched(TicketCreated::class, fn (TicketCreated $event): bool => $event->channel === TicketMessageChannel::Email);
});
