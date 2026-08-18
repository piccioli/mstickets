<?php

declare(strict_types=1);

use App\Domain\Mail\Contracts\InboundMailTransport;
use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Mail\Support\RawInboundEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Mail\FakeInboundMailTransport;

uses(RefreshDatabase::class);

function rawEmailFixture(string $filename): string
{
    $path = base_path("tests/Fixtures/emails/{$filename}");

    return file_get_contents($path) ?: throw new RuntimeException("Fixture .eml mancante: {$filename}");
}

test('archivia un nuovo messaggio come .eml prima di creare la riga email_messages', function (): void {
    Storage::fake('raw-emails');

    $raw = rawEmailFixture('richiesta-supporto.eml');

    $fake = new FakeInboundMailTransport([
        new RawInboundEmail(
            rawMessage: $raw,
            imapFolder: 'INBOX',
            imapUid: 101,
            messageId: '<req-001@example.test>',
            fromEmail: 'cliente@example.test',
            fromName: 'Mario Rossi',
            subject: 'Non riesco ad accedere al portale',
        ),
    ]);
    $this->app->instance(InboundMailTransport::class, $fake);

    $this->artisan('mail:fetch-inbound')->assertSuccessful();

    expect(EmailMessage::count())->toBe(1);

    $message = EmailMessage::query()->first();

    expect($message->direction)->toBe(EmailDirection::Inbound)
        // US-326: mail:fetch-inbound orchestra ora l'intera pipeline fino ad
        // ApplyInboundEmail; questo mittente non corrisponde a nessun utente,
        // quindi il messaggio finisce in quarantena (mai `received`).
        ->and($message->status)->toBe(EmailStatus::Quarantined)
        ->and($message->imap_folder)->toBe('INBOX')
        ->and($message->imap_uid)->toBe(101)
        ->and($message->message_id)->toBe('<req-001@example.test>')
        ->and($message->from_email)->toBe('cliente@example.test')
        ->and($message->from_name)->toBe('Mario Rossi')
        ->and($message->subject)->toBe('Non riesco ad accedere al portale')
        ->and($message->raw_path)->not->toBeNull();

    Storage::disk('raw-emails')->assertExists($message->raw_path);
    expect(Storage::disk('raw-emails')->get($message->raw_path))->toBe($raw);

    expect($fake->disconnected)->toBeTrue();
});

test('to, in_reply_to e references vengono archiviati per la risoluzione del thread (US-306)', function (): void {
    Storage::fake('raw-emails');

    $raw = rawEmailFixture('richiesta-supporto.eml');

    $fake = new FakeInboundMailTransport([
        new RawInboundEmail(
            rawMessage: $raw,
            imapFolder: 'INBOX',
            imapUid: 404,
            fromEmail: 'cliente@example.test',
            subject: 'Re: [#7] Ticket di test',
            to: ['ticket+01ARZ3NDEKTSV4RRFFQ69G5FAV@support.example.test'],
            inReplyTo: 'notifica-precedente@example.test',
            references: ['altra@example.test', 'notifica-precedente@example.test'],
        ),
    ]);
    $this->app->instance(InboundMailTransport::class, $fake);

    $this->artisan('mail:fetch-inbound')->assertSuccessful();

    $message = EmailMessage::query()->first();

    expect($message->to)->toBe(['ticket+01ARZ3NDEKTSV4RRFFQ69G5FAV@support.example.test'])
        ->and($message->in_reply_to)->toBe('notifica-precedente@example.test')
        ->and($message->references)->toBe('altra@example.test notifica-precedente@example.test');
});

test('un mittente senza Message-ID viene comunque archiviato', function (): void {
    Storage::fake('raw-emails');

    $raw = rawEmailFixture('mittente-senza-message-id.eml');

    $fake = new FakeInboundMailTransport([
        new RawInboundEmail(
            rawMessage: $raw,
            imapFolder: 'INBOX',
            imapUid: 202,
            messageId: null,
            fromEmail: 'utente.legacy@example.test',
            fromName: null,
            subject: 'Richiesta informazioni fattura',
        ),
    ]);
    $this->app->instance(InboundMailTransport::class, $fake);

    $this->artisan('mail:fetch-inbound')->assertSuccessful();

    $message = EmailMessage::query()->first();

    expect($message->message_id)->toBeNull()
        ->and($message->from_email)->toBe('utente.legacy@example.test');
});

test('rieseguire il comando sullo stesso stato IMAP non crea duplicati', function (): void {
    Storage::fake('raw-emails');

    $raw = rawEmailFixture('richiesta-supporto.eml');
    $message = new RawInboundEmail(
        rawMessage: $raw,
        imapFolder: 'INBOX',
        imapUid: 303,
        messageId: '<req-003@example.test>',
        fromEmail: 'cliente@example.test',
        subject: 'Subject invariato',
    );

    $this->app->instance(InboundMailTransport::class, new FakeInboundMailTransport([$message]));
    $this->artisan('mail:fetch-inbound')->assertSuccessful();

    $this->app->instance(InboundMailTransport::class, new FakeInboundMailTransport([$message]));
    $this->artisan('mail:fetch-inbound')->assertSuccessful();

    expect(EmailMessage::query()->where('imap_folder', 'INBOX')->where('imap_uid', 303)->count())->toBe(1);
});

test('rispetta --limit invece del default di configurazione', function (): void {
    Storage::fake('raw-emails');

    $messages = [
        new RawInboundEmail(rawMessage: 'a', imapFolder: 'INBOX', imapUid: 1, fromEmail: 'a@example.test'),
        new RawInboundEmail(rawMessage: 'b', imapFolder: 'INBOX', imapUid: 2, fromEmail: 'b@example.test'),
    ];

    $fake = new FakeInboundMailTransport($messages);
    $this->app->instance(InboundMailTransport::class, $fake);

    $this->artisan('mail:fetch-inbound', ['--limit' => 1])->assertSuccessful();

    expect($fake->lastLimit)->toBe(1)
        ->and(EmailMessage::count())->toBe(1);
});

test('senza --limit usa mail_pipeline.fetch.default_limit', function (): void {
    Storage::fake('raw-emails');

    config(['mail_pipeline.fetch.default_limit' => 7]);

    $fake = new FakeInboundMailTransport([]);
    $this->app->instance(InboundMailTransport::class, $fake);

    $this->artisan('mail:fetch-inbound')->assertSuccessful();

    expect($fake->lastLimit)->toBe(7);
});

test('disconnette sempre IMAP anche quando fetch lancia un errore', function (): void {
    config(['mail_pipeline.fetch.tries' => 1]);

    $fake = new FakeInboundMailTransport([], fetchException: new RuntimeException('connessione IMAP non riuscita'));
    $this->app->instance(InboundMailTransport::class, $fake);

    $this->artisan('mail:fetch-inbound')->assertFailed();

    expect($fake->fetchCalls)->toBe(1)
        ->and($fake->disconnected)->toBeTrue();
});
