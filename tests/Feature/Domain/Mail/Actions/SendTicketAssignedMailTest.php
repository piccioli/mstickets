<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Actions\SendTicketAssignedMail;
use App\Domain\Mail\Mailables\TicketAssignedMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('queues TicketAssignedMail to the new assignee when the actor is someone else', function (): void {
    Mail::fake();

    $actor = User::factory()->create();
    $assignee = User::factory()->create();
    $ticket = ticket();

    SendTicketAssignedMail::run($ticket, $assignee->id, asTester: false, actor: $actor);

    Mail::assertQueued(
        TicketAssignedMail::class,
        fn (TicketAssignedMail $mail): bool => $mail->ticket->is($ticket) && $mail->asTester === false,
    );
});

test('marks the mail as a tester assignment when asTester is true', function (): void {
    Mail::fake();

    $actor = User::factory()->create();
    $tester = User::factory()->create();
    $ticket = ticket();

    SendTicketAssignedMail::run($ticket, $tester->id, asTester: true, actor: $actor);

    Mail::assertQueued(
        TicketAssignedMail::class,
        fn (TicketAssignedMail $mail): bool => $mail->asTester === true,
    );
});

test('does not notify anyone when the new assignee performed the action themselves', function (): void {
    Mail::fake();

    $actor = User::factory()->create();
    $ticket = ticket();

    SendTicketAssignedMail::run($ticket, $actor->id, asTester: false, actor: $actor);

    Mail::assertNothingQueued();
});

test('does nothing when the given user id does not resolve to an existing user', function (): void {
    Mail::fake();

    $actor = User::factory()->create();
    $ticket = ticket();

    SendTicketAssignedMail::run($ticket, $actor->id + 999_999, asTester: false, actor: $actor);

    Mail::assertNothingQueued();
});

test('builds the subject in the assignee locale, not always Italian (§7.6, US-320)', function (): void {
    Mail::fake();

    $actor = User::factory()->create();
    $assignee = User::factory()->create(['locale' => 'en']);
    $ticket = ticket(['title' => 'Errore login SSO']);

    SendTicketAssignedMail::run($ticket, $assignee->id, asTester: false, actor: $actor);

    Mail::assertQueued(
        TicketAssignedMail::class,
        fn (TicketAssignedMail $mail): bool => $mail->outbound->subject === "[#{$ticket->id}] Ticket assigned: {$ticket->title}",
    );
});
