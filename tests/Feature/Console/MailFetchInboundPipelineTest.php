<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Mail\Contracts\InboundMailTransport;
use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Enums\SuppressionReason;
use App\Domain\Mail\Mailables\TicketReceivedByEmailMail;
use App\Domain\Mail\Mailables\UnknownSenderStaffMail;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Mail\Models\EmailSuppression;
use App\Domain\Mail\Support\RawInboundEmail;
use App\Domain\Ticketing\Enums\TicketMessageChannel;
use App\Domain\Ticketing\Enums\TicketType;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketMessage;
use App\Filament\Resources\EmailMessages\Pages\ListEmailMessages;
use App\Filament\Resources\EmailMessages\Pages\ViewEmailMessage;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\Support\Mail\FakeInboundMailTransport;

uses(RefreshDatabase::class);

/**
 * Checkpoint di fine Fase 3 (§14 del PRD, US-326): verifica l'intera pipeline
 * inbound `Artisan::call('mail:fetch-inbound')` → parse → classify →
 * apply/DSN su fixture `.eml` realistiche, una per ciascuno dei criteri di
 * accettazione espliciti del PRD principale — non solo la suite unitaria
 * story-per-story già esistente.
 */
function checkpointFixture(string $filename, array $replacements = []): string
{
    $raw = file_get_contents(base_path("tests/Fixtures/emails/{$filename}"))
        ?: throw new RuntimeException("Fixture .eml mancante: {$filename}");

    return strtr($raw, $replacements);
}

function runFetchInbound(array $rawMessages): FakeInboundMailTransport
{
    $fake = new FakeInboundMailTransport($rawMessages);
    app()->instance(InboundMailTransport::class, $fake);
    test()->artisan('mail:fetch-inbound')->assertSuccessful();

    return $fake;
}

function grantCheckpointPanelAccess(User $user): User
{
    Role::query()->firstOrCreate(['name' => UserRole::Developer->value, 'guard_name' => 'web']);
    $user->assignRole(UserRole::Developer->value);

    return $user->fresh();
}

beforeEach(function (): void {
    Storage::fake('raw-emails');
    Filament::setCurrentPanel('admin');
});

test('una risposta via VERP a una notifica accoda un messaggio sul ticket esistente invece di crearne uno nuovo', function (): void {
    $requester = User::factory()->create(['email' => 'cliente.verp@example.test', 'locale' => 'it']);
    $ticket = Ticket::create([
        'title' => 'Ticket con notifica in corso',
        'status_changed_at' => now(),
        'requester_id' => $requester->id,
        'type' => TicketType::Helpdesk,
    ]);
    $staffMessage = TicketMessage::create([
        'ticket_id' => $ticket->id,
        'channel' => TicketMessageChannel::Email,
        'posted_at' => now(),
    ]);

    $raw = checkpointFixture('checkpoint-risposta-notifica-verp.eml', [
        '__TICKET_MESSAGE_ULID__' => $staffMessage->ulid,
    ]);

    runFetchInbound([
        new RawInboundEmail(
            rawMessage: $raw,
            imapFolder: 'INBOX',
            imapUid: 5001,
            messageId: '<checkpoint-verp-001@example.test>',
            fromEmail: 'cliente.verp@example.test',
            fromName: 'Cliente VERP',
            subject: 'Re: Aggiornamento sulla mia richiesta',
            to: ["ticket+{$staffMessage->ulid}@support.example.test"],
        ),
    ]);

    expect(Ticket::count())->toBe(1);

    $ticket->refresh();
    expect($ticket->messages)->toHaveCount(2);

    $emailMessage = EmailMessage::query()->where('imap_uid', 5001)->firstOrFail();
    expect($emailMessage->status)->toBe(EmailStatus::Applied)
        ->and($emailMessage->ticket_id)->toBe($ticket->id);
});

test('una risposta su un ticket importato dal v1 risolve via token subject anche senza VERP disponibile', function (): void {
    $requester = User::factory()->create(['email' => 'cliente.storico@example.test', 'locale' => 'it']);
    $legacyTicket = Ticket::create([
        'title' => 'Vecchio ticket migrato dal v1',
        'status_changed_at' => now(),
        'requester_id' => $requester->id,
        'type' => TicketType::Helpdesk,
    ]);

    $raw = checkpointFixture('checkpoint-risposta-ticket-v1-subject-token.eml', [
        '__TICKET_ID__' => (string) $legacyTicket->id,
    ]);

    runFetchInbound([
        new RawInboundEmail(
            rawMessage: $raw,
            imapFolder: 'INBOX',
            imapUid: 5002,
            messageId: '<checkpoint-subject-token-001@example.test>',
            fromEmail: 'cliente.storico@example.test',
            fromName: 'Cliente Storico',
            subject: "Re: [#{$legacyTicket->id}] Vecchio ticket migrato dal v1",
            to: ['supporto@example.test'],
        ),
    ]);

    expect(Ticket::count())->toBe(1);

    $legacyTicket->refresh();
    expect($legacyTicket->messages)->toHaveCount(1);

    $emailMessage = EmailMessage::query()->where('imap_uid', 5002)->firstOrFail();
    expect($emailMessage->status)->toBe(EmailStatus::Applied)
        ->and($emailMessage->ticket_id)->toBe($legacyTicket->id);
});

test('un hard bounce sospende permanentemente il destinatario originale, non crea ticket e non genera auto-reply', function (): void {
    Mail::fake();

    EmailMessage::query()->forceCreate([
        'direction' => EmailDirection::Outbound,
        'status' => EmailStatus::Queued,
        'from_email' => 'supporto@example.test',
        'to' => ['destinatario.rimbalzato@example.test'],
        'message_id' => 'checkpoint-outbound-hard-bounce@example.test',
        'subject' => '[#1] Notifica ticket',
    ]);

    $raw = checkpointFixture('checkpoint-dsn-hard-bounce.eml');

    runFetchInbound([
        new RawInboundEmail(
            rawMessage: $raw,
            imapFolder: 'INBOX',
            imapUid: 5003,
            messageId: '<checkpoint-dsn-hard-bounce@example.test>',
            fromEmail: 'mailer-daemon@relay.example.test',
            subject: 'Undelivered Mail Returned to Sender',
        ),
    ]);

    $suppression = EmailSuppression::query()->where('email', 'destinatario.rimbalzato@example.test')->first();
    expect($suppression)->not->toBeNull()
        ->and($suppression->reason)->toBe(SuppressionReason::HardBounce)
        ->and($suppression->expires_at)->toBeNull();

    expect(EmailMessage::query()->where('message_id', 'checkpoint-outbound-hard-bounce@example.test')->firstOrFail()->status)
        ->toBe(EmailStatus::Bounced);

    expect(Ticket::count())->toBe(0);
    Mail::assertNothingQueued();
    Mail::assertNothingSent();
});

test('un mittente già in blacklist anti-loop viene scartato e riprocessare lo stesso messaggio non duplica nulla', function (): void {
    EmailSuppression::create([
        'email' => 'bounce@dominio-bloccato.test',
        'reason' => SuppressionReason::LoopProtection,
        'expires_at' => now()->addHours(24),
    ]);

    $raw = checkpointFixture('checkpoint-mittente-in-blacklist-anti-loop.eml');
    $message = new RawInboundEmail(
        rawMessage: $raw,
        imapFolder: 'INBOX',
        imapUid: 5004,
        messageId: '<checkpoint-blacklisted-sender@example.test>',
        fromEmail: 'bounce@dominio-bloccato.test',
        subject: 'Risposta automatica ripetuta',
    );

    runFetchInbound([$message]);

    expect(EmailMessage::count())->toBe(1);
    $emailMessage = EmailMessage::query()->firstOrFail();
    expect($emailMessage->status)->toBe(EmailStatus::Discarded)
        ->and(Ticket::count())->toBe(0);

    // Rieseguire il fetch sullo stesso (imap_folder, imap_uid) — lo stesso
    // stato IMAP — non deve produrre un secondo record né riprocessare nulla.
    runFetchInbound([$message]);

    expect(EmailMessage::count())->toBe(1);
});

test('un mittente sconosciuto va in quarantena, resta ispezionabile in amministrazione (US-321) insieme a un messaggio scartato', function (): void {
    EmailSuppression::create([
        'email' => 'bounce@dominio-bloccato.test',
        'reason' => SuppressionReason::LoopProtection,
        'expires_at' => now()->addHours(24),
    ]);

    runFetchInbound([
        new RawInboundEmail(
            rawMessage: checkpointFixture('checkpoint-mittente-sconosciuto.eml'),
            imapFolder: 'INBOX',
            imapUid: 5005,
            messageId: '<checkpoint-unknown-sender@example.test>',
            fromEmail: 'estraneo@example.test',
            subject: 'Informazioni sui vostri servizi',
        ),
        new RawInboundEmail(
            rawMessage: checkpointFixture('checkpoint-mittente-in-blacklist-anti-loop.eml'),
            imapFolder: 'INBOX',
            imapUid: 5006,
            messageId: '<checkpoint-blacklisted-sender-2@example.test>',
            fromEmail: 'bounce@dominio-bloccato.test',
            subject: 'Risposta automatica ripetuta',
        ),
    ]);

    $quarantined = EmailMessage::query()->where('imap_uid', 5005)->firstOrFail();
    $discarded = EmailMessage::query()->where('imap_uid', 5006)->firstOrFail();

    expect($quarantined->status)->toBe(EmailStatus::Quarantined)
        ->and($discarded->status)->toBe(EmailStatus::Discarded);

    $staff = grantCheckpointPanelAccess(userWithPermissions(PermissionEnum::EmailView));

    Livewire::actingAs($staff)
        ->test(ListEmailMessages::class)
        ->assertCanSeeTableRecords([$quarantined, $discarded]);

    Livewire::actingAs($staff)
        ->test(ViewEmailMessage::class, ['record' => $quarantined->getKey()])
        ->assertOk();

    Livewire::actingAs($staff)
        ->test(ViewEmailMessage::class, ['record' => $discarded->getKey()])
        ->assertOk();
});

test('la conferma di apertura ticket via email arriva nella lingua del richiedente (US-320) attraverso tutta la pipeline', function (): void {
    Mail::fake();

    User::factory()->create(['email' => 'cliente.en@example.test', 'locale' => 'en']);

    $raw = <<<'EML'
    Return-Path: <cliente.en@example.test>
    From: English Customer <cliente.en@example.test>
    To: Supporto <supporto@example.test>
    Subject: I cannot log in
    Message-ID: <checkpoint-locale-en@example.test>
    Date: Mon, 09 Feb 2026 11:00:00 +0100
    Content-Type: text/plain; charset=UTF-8
    Content-Transfer-Encoding: 7bit
    MIME-Version: 1.0

    Hello, I cannot log in to the portal anymore, could you please help?
    EML;

    runFetchInbound([
        new RawInboundEmail(
            rawMessage: $raw,
            imapFolder: 'INBOX',
            imapUid: 5007,
            messageId: '<checkpoint-locale-en@example.test>',
            fromEmail: 'cliente.en@example.test',
            fromName: 'English Customer',
            subject: 'I cannot log in',
        ),
    ]);

    $emailMessage = EmailMessage::query()->where('imap_uid', 5007)->firstOrFail();
    expect($emailMessage->status)->toBe(EmailStatus::Applied)
        ->and($emailMessage->ticket_id)->not->toBeNull();

    Mail::assertQueued(
        TicketReceivedByEmailMail::class,
        fn (TicketReceivedByEmailMail $mail): bool => $mail->locale === 'en',
    );
    Mail::assertNotQueued(UnknownSenderStaffMail::class);
});
