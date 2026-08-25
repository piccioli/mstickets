<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Listeners\SendTicketAssignedNotification;
use App\Domain\Mail\Mailables\TicketAssignedMail;
use App\Domain\Ticketing\Events\TicketAssigned;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('sends E6 to the new assignee when TicketAssigned is dispatched', function (): void {
    Mail::fake();

    $actor = User::factory()->create();
    $assignee = User::factory()->create();
    $ticket = ticket();

    (new SendTicketAssignedNotification)->handle(
        new TicketAssigned($ticket, null, $assignee->id, $actor),
    );

    Mail::assertQueued(
        TicketAssignedMail::class,
        fn (TicketAssignedMail $mail): bool => $mail->asTester === false,
    );
});

test('implements ShouldQueue so the send happens asynchronously', function (): void {
    expect(new SendTicketAssignedNotification)->toBeInstanceOf(ShouldQueue::class);
});
