<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Mail\Listeners\SendTicketStatusChangedNotification;
use App\Domain\Mail\Mailables\TicketStatusChangedMail;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Events\TicketStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('sends E4 to the ticket recipients when the status changes', function (): void {
    Mail::fake();

    $requester = withRole(User::factory()->create(), UserRole::Customer);
    $actor = withRole(User::factory()->create(), UserRole::Manager);
    $ticket = ticket(['requester_id' => $requester->id]);

    (new SendTicketStatusChangedNotification)->handle(
        new TicketStatusChanged($ticket, TicketStatus::New, TicketStatus::Rejected, $actor),
    );

    Mail::assertQueued(TicketStatusChangedMail::class);
});

test('implements ShouldQueue so the send happens asynchronously', function (): void {
    expect(new SendTicketStatusChangedNotification)->toBeInstanceOf(ShouldQueue::class);
});
