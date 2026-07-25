<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Ticketing\Enums\TicketMessageChannel;
use App\Domain\Ticketing\Enums\TicketMessageVisibility;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketMessage;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function makeTicket(): Ticket
{
    return Ticket::create(['title' => 'Ticket di test', 'status_changed_at' => now()]);
}

test('ticket_messages table has the columns required by §5.2', function (): void {
    expect(Schema::hasColumns('ticket_messages', [
        'id', 'ulid', 'ticket_id', 'author_id', 'author_email', 'channel', 'visibility',
        'body_html', 'body_text', 'email_message_id', 'is_legacy_import', 'posted_at',
        'created_at', 'updated_at',
    ]))->toBeTrue();
});

test('a ulid is generated automatically on creation, id stays the auto-increment primary key', function (): void {
    $message = TicketMessage::create([
        'ticket_id' => makeTicket()->id,
        'channel' => TicketMessageChannel::Web,
        'body_text' => 'Ciao',
        'posted_at' => now(),
    ]);

    expect($message->ulid)->not->toBeEmpty()
        ->and(Str::isUlid($message->ulid))->toBeTrue()
        ->and($message->getKeyName())->toBe('id')
        ->and($message->id)->toBeInt();
});

test('the ulid is unique', function (): void {
    $ticket = makeTicket();
    $first = TicketMessage::create([
        'ticket_id' => $ticket->id, 'channel' => TicketMessageChannel::Web, 'posted_at' => now(),
    ]);

    // `ulid` non è mass-assignable (§5.2, generato da HasUlids): forzato via assegnazione
    // diretta dell'attributo per verificare il vincolo unique a livello di DB.
    $duplicate = new TicketMessage([
        'ticket_id' => $ticket->id, 'channel' => TicketMessageChannel::Web, 'posted_at' => now(),
    ]);
    $duplicate->ulid = $first->ulid;

    expect(fn () => $duplicate->save())->toThrow(QueryException::class);
});

test('channel and visibility are cast to their backed enum, visibility defaults to public', function (): void {
    $message = TicketMessage::create([
        'ticket_id' => makeTicket()->id,
        'channel' => TicketMessageChannel::Email,
        'posted_at' => now(),
    ]);

    expect($message->fresh()->channel)->toBe(TicketMessageChannel::Email)
        ->and($message->fresh()->visibility)->toBe(TicketMessageVisibility::Public);
});

test('deleting the ticket cascades to its messages', function (): void {
    $ticket = makeTicket();
    $message = TicketMessage::create([
        'ticket_id' => $ticket->id, 'channel' => TicketMessageChannel::Web, 'posted_at' => now(),
    ]);

    $ticket->forceDelete();

    expect(TicketMessage::find($message->id))->toBeNull();
});

test('deleting the author sets author_id to null', function (): void {
    $author = User::factory()->create();
    $message = TicketMessage::create([
        'ticket_id' => makeTicket()->id,
        'author_id' => $author->id,
        'channel' => TicketMessageChannel::Web,
        'posted_at' => now(),
    ]);

    $author->forceDelete();

    expect($message->fresh()->author_id)->toBeNull();
});

test('the attachments media collection is registered', function (): void {
    $message = TicketMessage::create([
        'ticket_id' => makeTicket()->id, 'channel' => TicketMessageChannel::Web, 'posted_at' => now(),
    ]);

    expect($message->getMediaCollection('attachments'))->not->toBeNull();
});

test('deleting the linked email message sets email_message_id to null', function (): void {
    $email = EmailMessage::create([
        'direction' => EmailDirection::Inbound,
        'from_email' => 'mittente@example.com',
        'status' => EmailStatus::Received,
    ]);
    $message = TicketMessage::create([
        'ticket_id' => makeTicket()->id,
        'channel' => TicketMessageChannel::Email,
        'email_message_id' => $email->id,
        'posted_at' => now(),
    ]);

    $email->delete();

    expect($message->fresh()->email_message_id)->toBeNull();
});

test('belongs to an email message', function (): void {
    $email = EmailMessage::create([
        'direction' => EmailDirection::Inbound,
        'from_email' => 'mittente@example.com',
        'status' => EmailStatus::Received,
    ]);
    $message = TicketMessage::create([
        'ticket_id' => makeTicket()->id,
        'channel' => TicketMessageChannel::Email,
        'email_message_id' => $email->id,
        'posted_at' => now(),
    ]);

    expect($message->emailMessage->is($email))->toBeTrue();
});
