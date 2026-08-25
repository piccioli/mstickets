<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Listeners\SendTicketTesterAssignedNotification;
use App\Domain\Mail\Mailables\TicketAssignedMail;
use App\Domain\Ticketing\Events\TicketTesterAssigned;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('sends E6 to the new tester when TicketTesterAssigned is dispatched', function (): void {
    Mail::fake();

    $actor = User::factory()->create();
    $tester = User::factory()->create();
    $ticket = ticket();

    (new SendTicketTesterAssignedNotification)->handle(
        new TicketTesterAssigned($ticket, null, $tester->id, $actor),
    );

    Mail::assertQueued(
        TicketAssignedMail::class,
        fn (TicketAssignedMail $mail): bool => $mail->asTester === true,
    );
});

test('implements ShouldQueue so the send happens asynchronously', function (): void {
    expect(new SendTicketTesterAssignedNotification)->toBeInstanceOf(ShouldQueue::class);
});
