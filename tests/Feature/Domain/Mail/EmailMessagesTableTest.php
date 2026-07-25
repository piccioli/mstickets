<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Mail\Models\EmailThread;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function makeEmailMessage(array $attributes = []): EmailMessage
{
    return EmailMessage::create(array_merge([
        'direction' => EmailDirection::Inbound,
        'from_email' => 'mittente@example.com',
        'status' => EmailStatus::Received,
    ], $attributes));
}

test('email_messages table has the columns required by §5.2', function (): void {
    expect(Schema::hasColumns('email_messages', [
        'id', 'ulid', 'direction', 'message_id', 'in_reply_to', 'references', 'thread_id',
        'ticket_id', 'user_id', 'from_email', 'from_name', 'to', 'cc', 'bcc', 'reply_to',
        'subject', 'body_text', 'body_html', 'raw_path', 'status', 'failure_reason', 'attempts',
        'mailable_class', 'provider_message_id', 'imap_uid', 'imap_folder', 'content_hash',
        'received_at', 'sent_at', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

test('a ulid is generated automatically on creation, id stays the auto-increment primary key', function (): void {
    $message = makeEmailMessage();

    expect($message->ulid)->not->toBeEmpty()
        ->and(Str::isUlid($message->ulid))->toBeTrue()
        ->and($message->getKeyName())->toBe('id')
        ->and($message->id)->toBeInt();
});

test('direction, status, to, cc and bcc are cast', function (): void {
    $message = makeEmailMessage([
        'direction' => EmailDirection::Outbound,
        'status' => EmailStatus::Queued,
        'to' => [['email' => 'a@example.com']],
        'cc' => [['email' => 'b@example.com']],
        'bcc' => [['email' => 'c@example.com']],
    ]);

    $fresh = $message->fresh();

    expect($fresh->direction)->toBe(EmailDirection::Outbound)
        ->and($fresh->status)->toBe(EmailStatus::Queued)
        ->and($fresh->to)->toBe([['email' => 'a@example.com']])
        ->and($fresh->cc)->toBe([['email' => 'b@example.com']])
        ->and($fresh->bcc)->toBe([['email' => 'c@example.com']]);
});

test('attempts defaults to 0', function (): void {
    expect(makeEmailMessage()->fresh()->attempts)->toBe(0);
});

test('deleting the thread sets thread_id to null', function (): void {
    $thread = EmailThread::create(['subject_normalized' => 'ciao']);
    $message = makeEmailMessage(['thread_id' => $thread->id]);

    $thread->delete();

    expect($message->fresh()->thread_id)->toBeNull();
});

test('deleting the ticket sets ticket_id to null', function (): void {
    $ticket = Ticket::create(['title' => 'Ticket di test', 'status_changed_at' => now()]);
    $message = makeEmailMessage(['ticket_id' => $ticket->id]);

    $ticket->forceDelete();

    expect($message->fresh()->ticket_id)->toBeNull();
});

test('deleting the user sets user_id to null', function (): void {
    $user = User::factory()->create();
    $message = makeEmailMessage(['user_id' => $user->id]);

    $user->forceDelete();

    expect($message->fresh()->user_id)->toBeNull();
});

test('(direction, message_id) is unique only when message_id is not null', function (): void {
    makeEmailMessage(['message_id' => '<abc@example.com>']);

    expect(fn () => makeEmailMessage(['message_id' => '<abc@example.com>']))
        ->toThrow(QueryException::class);

    // Due righe con message_id NULL sulla stessa direction non violano il vincolo.
    makeEmailMessage();
    makeEmailMessage();

    expect(EmailMessage::whereNull('message_id')->count())->toBe(2);
});

test('(imap_folder, imap_uid) is unique', function (): void {
    makeEmailMessage(['imap_folder' => 'INBOX', 'imap_uid' => 42]);

    expect(fn () => makeEmailMessage(['imap_folder' => 'INBOX', 'imap_uid' => 42]))
        ->toThrow(QueryException::class);

    // Righe outbound senza imap_folder/imap_uid (entrambi NULL) non violano il vincolo.
    makeEmailMessage(['direction' => EmailDirection::Outbound, 'status' => EmailStatus::Queued]);
    makeEmailMessage(['direction' => EmailDirection::Outbound, 'status' => EmailStatus::Queued]);

    expect(EmailMessage::whereNull('imap_folder')->count())->toBe(2);
});
