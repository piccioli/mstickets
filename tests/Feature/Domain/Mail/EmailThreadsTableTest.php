<?php

declare(strict_types=1);

use App\Domain\Mail\Models\EmailThread;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function makeTicketForThread(): Ticket
{
    return Ticket::create(['title' => 'Ticket di test', 'status_changed_at' => now()]);
}

test('email_threads table has the columns required by §5.2', function (): void {
    expect(Schema::hasColumns('email_threads', [
        'id', 'ticket_id', 'subject_normalized', 'participants', 'last_message_at',
        'created_at', 'updated_at',
    ]))->toBeTrue();
});

test('participants is cast to an array', function (): void {
    $thread = EmailThread::create([
        'subject_normalized' => 'assistenza account',
        'participants' => [['email' => 'a@example.com'], ['email' => 'b@example.com']],
    ]);

    expect($thread->fresh()->participants)->toBe([
        ['email' => 'a@example.com'], ['email' => 'b@example.com'],
    ]);
});

test('belongs to a ticket, cascading on delete', function (): void {
    $ticket = makeTicketForThread();
    $thread = EmailThread::create(['ticket_id' => $ticket->id, 'subject_normalized' => 'ciao']);

    expect($thread->ticket->is($ticket))->toBeTrue();

    $ticket->forceDelete();

    expect(EmailThread::find($thread->id))->toBeNull();
});

test('ticket_id can be null (thread not yet linked to a ticket)', function (): void {
    $thread = EmailThread::create(['subject_normalized' => 'senza ticket']);

    expect($thread->fresh()->ticket_id)->toBeNull();
});
