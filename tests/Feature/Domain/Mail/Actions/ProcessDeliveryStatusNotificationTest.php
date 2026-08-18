<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Actions\ProcessDeliveryStatusNotification;
use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Enums\SuppressionReason;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Mail\Models\EmailSuppression;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Enums\TicketType;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/**
 * Costruisce un DSN realistico (RFC 3464): tre parti multipart/report
 * (text/plain leggibile, message/delivery-status con Action/Status/Final-
 * Recipient, message/rfc822 con gli header del messaggio originale incluso
 * il suo Message-ID) — la stessa struttura che ClassifyInboundEmail (US-304)
 * riconosce dal solo Content-Type di livello superiore, qui serve anche il
 * contenuto delle parti interne.
 */
function buildRealisticDsn(string $action, string $status, ?string $recipient, ?string $originalMessageId): string
{
    $boundary = 'boundary_'.Str::random(8);
    $recipientLine = $recipient !== null ? "Final-Recipient: rfc822; {$recipient}\r\n" : '';
    $originalHeaders = "From: cliente@example.test\r\nTo: supporto@example.test\r\nSubject: Richiesta\r\n"
        .($originalMessageId !== null ? "Message-ID: <{$originalMessageId}>\r\n" : '');

    return "From: mailer@relay.example.test\r\n"
        ."To: supporto@example.test\r\n"
        ."Subject: Undelivered Mail Returned to Sender\r\n"
        ."Content-Type: multipart/report; report-type=delivery-status; boundary=\"{$boundary}\"\r\n"
        ."\r\n"
        ."--{$boundary}\r\n"
        ."Content-Type: text/plain\r\n"
        ."\r\n"
        ."Notifica di mancato recapito.\r\n"
        ."\r\n"
        ."--{$boundary}\r\n"
        ."Content-Type: message/delivery-status\r\n"
        ."\r\n"
        ."Reporting-MTA: dns; relay.example.test\r\n"
        ."\r\n"
        .$recipientLine
        ."Action: {$action}\r\n"
        ."Status: {$status}\r\n"
        ."\r\n"
        ."--{$boundary}\r\n"
        ."Content-Type: message/rfc822\r\n"
        ."\r\n"
        .$originalHeaders
        ."\r\n"
        ."Corpo originale.\r\n"
        ."\r\n"
        ."--{$boundary}--\r\n";
}

function storeDsnFixture(string $raw): EmailMessage
{
    $rawPath = Str::ulid()->toString().'.eml';

    Storage::disk('raw-emails')->put($rawPath, $raw);

    return EmailMessage::create([
        'direction' => EmailDirection::Inbound,
        'status' => EmailStatus::Discarded,
        'from_email' => 'mailer@relay.example.test',
        'raw_path' => $rawPath,
        'subject' => 'Undelivered Mail Returned to Sender',
        'failure_reason' => 'delivery_status_notification',
        'received_at' => now(),
    ]);
}

beforeEach(function (): void {
    Storage::fake('raw-emails');
    config(['mail_pipeline.bounce.soft_bounce_threshold' => 3]);
});

test('un hard bounce (Action: failed) sospende permanentemente il destinatario originale', function (): void {
    $dsn = storeDsnFixture(buildRealisticDsn('failed', '5.1.1', 'cliente@example.test', null));

    ProcessDeliveryStatusNotification::run($dsn);

    $suppression = EmailSuppression::query()->where('email', 'cliente@example.test')->first();

    expect($suppression)->not->toBeNull()
        ->and($suppression->reason)->toBe(SuppressionReason::HardBounce)
        ->and($suppression->expires_at)->toBeNull()
        ->and(EmailSuppression::query()->active()->where('email', 'cliente@example.test')->exists())->toBeTrue();
});

test('un hard bounce riconosciuto dal solo codice Status 5.x.x (senza Action) sospende comunque', function (): void {
    $dsn = storeDsnFixture(buildRealisticDsn('', '5.4.1', 'cliente@example.test', null));

    ProcessDeliveryStatusNotification::run($dsn);

    expect(EmailSuppression::query()->where('email', 'cliente@example.test')->first()?->reason)
        ->toBe(SuppressionReason::HardBounce);
});

test('un soft bounce sotto soglia incrementa bounce_count senza attivare la sospensione', function (): void {
    $dsn = storeDsnFixture(buildRealisticDsn('delayed', '4.2.1', 'cliente@example.test', null));

    ProcessDeliveryStatusNotification::run($dsn);

    $suppression = EmailSuppression::query()->where('email', 'cliente@example.test')->first();

    expect($suppression)->not->toBeNull()
        ->and($suppression->reason)->toBe(SuppressionReason::SoftBounce)
        ->and($suppression->bounce_count)->toBe(1)
        ->and(EmailSuppression::query()->active()->where('email', 'cliente@example.test')->exists())->toBeFalse();
});

test('un soft bounce che raggiunge la soglia configurata attiva la sospensione', function (): void {
    EmailSuppression::query()->create([
        'email' => 'cliente@example.test',
        'reason' => SuppressionReason::SoftBounce,
        'bounce_count' => 2,
        'expires_at' => now(),
    ]);

    $dsn = storeDsnFixture(buildRealisticDsn('delayed', '4.2.1', 'cliente@example.test', null));

    ProcessDeliveryStatusNotification::run($dsn);

    $suppression = EmailSuppression::query()->where('email', 'cliente@example.test')->first();

    expect($suppression->bounce_count)->toBe(3)
        ->and($suppression->expires_at)->toBeNull()
        ->and(EmailSuppression::query()->active()->where('email', 'cliente@example.test')->exists())->toBeTrue();
});

test('un soft bounce successivo non retrocede una sospensione già hard bounce', function (): void {
    EmailSuppression::query()->create([
        'email' => 'cliente@example.test',
        'reason' => SuppressionReason::HardBounce,
        'expires_at' => null,
    ]);

    $dsn = storeDsnFixture(buildRealisticDsn('delayed', '4.2.1', 'cliente@example.test', null));

    ProcessDeliveryStatusNotification::run($dsn);

    expect(EmailSuppression::query()->where('email', 'cliente@example.test')->first()?->reason)
        ->toBe(SuppressionReason::HardBounce);
});

test('il DSN è correlato al ticket dell\'email originale via Message-ID citato nel report', function (): void {
    $requester = User::factory()->create(['email' => 'cliente@example.test']);

    $ticket = Ticket::create([
        'title' => 'Richiesta originale',
        'status' => TicketStatus::Todo,
        'status_changed_at' => now(),
        'requester_id' => $requester->id,
        'type' => TicketType::Helpdesk,
    ]);

    $originalMessageId = Str::ulid()->toString().'@example.test';

    EmailMessage::query()->forceCreate([
        'ulid' => (string) Str::ulid(),
        'direction' => EmailDirection::Outbound,
        'message_id' => $originalMessageId,
        'ticket_id' => $ticket->id,
        'from_email' => 'supporto@example.test',
        'to' => ['cliente@example.test'],
        'status' => EmailStatus::Sent,
        'subject' => 'Conferma ricezione',
    ]);

    $dsn = storeDsnFixture(buildRealisticDsn('failed', '5.1.1', null, $originalMessageId));

    $result = ProcessDeliveryStatusNotification::run($dsn);

    expect($result->ticket_id)->toBe($ticket->id);
});

test('un hard bounce correlato aggiorna anche lo stato dell\'email originale a bounced', function (): void {
    $originalMessageId = Str::ulid()->toString().'@example.test';

    $original = EmailMessage::query()->forceCreate([
        'ulid' => (string) Str::ulid(),
        'direction' => EmailDirection::Outbound,
        'message_id' => $originalMessageId,
        'from_email' => 'supporto@example.test',
        'to' => ['cliente@example.test'],
        'status' => EmailStatus::Sent,
        'subject' => 'Conferma ricezione',
    ]);

    $dsn = storeDsnFixture(buildRealisticDsn('failed', '5.1.1', null, $originalMessageId));

    ProcessDeliveryStatusNotification::run($dsn);

    expect($original->refresh()->status)->toBe(EmailStatus::Bounced)
        ->and(EmailSuppression::query()->where('email', 'cliente@example.test')->exists())->toBeTrue();
});

test('nessun destinatario ricavabile (né Final-Recipient né email originale correlata) non genera nessuna soppressione', function (): void {
    $dsn = storeDsnFixture(buildRealisticDsn('failed', '5.1.1', null, null));

    $result = ProcessDeliveryStatusNotification::run($dsn);

    expect($result->status)->toBe(EmailStatus::Discarded)
        ->and(EmailSuppression::query()->count())->toBe(0);
});

test('un .eml grezzo mancante non lancia eccezioni e lascia il messaggio invariato', function (): void {
    $dsn = EmailMessage::create([
        'direction' => EmailDirection::Inbound,
        'status' => EmailStatus::Discarded,
        'from_email' => 'mailer@relay.example.test',
        'raw_path' => 'non-esiste.eml',
        'subject' => 'Undelivered Mail Returned to Sender',
        'failure_reason' => 'delivery_status_notification',
        'received_at' => now(),
    ]);

    $result = ProcessDeliveryStatusNotification::run($dsn);

    expect($result->status)->toBe(EmailStatus::Discarded)
        ->and(EmailSuppression::query()->count())->toBe(0);
});
