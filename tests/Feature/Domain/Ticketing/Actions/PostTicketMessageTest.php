<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Actions\PostTicketMessage;
use App\Domain\Ticketing\Enums\TicketLogEvent;
use App\Domain\Ticketing\Enums\TicketMessageChannel;
use App\Domain\Ticketing\Enums\TicketMessageVisibility;
use App\Domain\Ticketing\Events\TicketMessagePosted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('creates a public web message with sanitized html and a derived plain text body', function (): void {
    $author = User::factory()->create();
    $t = ticket();

    $message = PostTicketMessage::run($t, $author, '<p>Ciao <script>alert(1)</script><strong>mondo</strong></p>');

    expect($message->ticket_id)->toBe($t->id)
        ->and($message->author_id)->toBe($author->id)
        ->and($message->channel)->toBe(TicketMessageChannel::Web)
        ->and($message->visibility)->toBe(TicketMessageVisibility::Public)
        ->and($message->is_legacy_import)->toBeFalse()
        ->and($message->body_html)->toContain('<strong>mondo</strong>')
        ->and($message->body_html)->not->toContain('<script')
        ->and($message->body_text)->toBe('Ciao mondo')
        ->and($message->posted_at)->not->toBeNull();
});

test('adds the author to ticket participants if not already present', function (): void {
    $author = User::factory()->create();
    $t = ticket();

    expect($t->participants()->where('user_id', $author->id)->exists())->toBeFalse();

    PostTicketMessage::run($t, $author, '<p>Ciao</p>');

    expect($t->participants()->where('user_id', $author->id)->exists())->toBeTrue();
});

test('does not duplicate the participant row if the author already participates', function (): void {
    $author = User::factory()->create();
    $t = ticket();
    $t->participants()->attach($author->id);

    PostTicketMessage::run($t, $author, '<p>Ciao</p>');

    expect($t->participants()->where('user_id', $author->id)->count())->toBe(1);
});

test('writes a message_posted ticket_log attributed to the author', function (): void {
    $author = User::factory()->create();
    $t = ticket();

    $message = PostTicketMessage::run($t, $author, '<p>Ciao</p>');

    $log = $t->logs()->sole();

    expect($log->event)->toBe(TicketLogEvent::MessagePosted)
        ->and($log->user_id)->toBe($author->id)
        ->and($log->is_system)->toBeFalse();
});

test('emits TicketMessagePosted with the ticket and the created message', function (): void {
    Event::fake();

    $author = User::factory()->create();
    $t = ticket();

    $message = PostTicketMessage::run($t, $author, '<p>Ciao</p>');

    Event::assertDispatched(TicketMessagePosted::class, function (TicketMessagePosted $event) use ($t, $message): bool {
        return $event->ticket->is($t) && $event->message->is($message);
    });
});

test('a system author writing a message writes an is_system ticket_log', function (): void {
    $system = systemUser();
    $t = ticket();

    PostTicketMessage::run($t, $system, '<p>Ciao</p>');

    expect($t->logs()->sole()->is_system)->toBeTrue();
});

test('rolls back everything if a listener fails while handling TicketMessagePosted', function (): void {
    $author = User::factory()->create();
    $t = ticket();

    Event::listen(TicketMessagePosted::class, function (): void {
        throw new RuntimeException('Simulated listener failure.');
    });

    expect(fn () => PostTicketMessage::run($t, $author, '<p>Ciao</p>'))
        ->toThrow(RuntimeException::class);

    expect($t->messages()->count())->toBe(0)
        ->and($t->logs()->count())->toBe(0)
        ->and($t->participants()->count())->toBe(0);
});
