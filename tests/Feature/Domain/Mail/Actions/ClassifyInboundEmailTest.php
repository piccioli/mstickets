<?php

declare(strict_types=1);

use App\Domain\Mail\Actions\ClassifyInboundEmail;
use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailDiscardReason;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Enums\SuppressionReason;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Mail\Models\EmailSuppression;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/**
 * @param  array<string, string>  $headerOverrides
 */
function buildRawEmail(array $headerOverrides = [], string $contentType = 'text/plain'): string
{
    $headers = array_merge([
        'From' => 'mittente@example.test',
        'To' => 'ticket@example.test',
        'Subject' => 'Richiesta di test',
    ], $headerOverrides);

    $lines = array_map(fn (string $name, string $value): string => "{$name}: {$value}", array_keys($headers), $headers);
    $lines[] = "Content-Type: {$contentType}";

    return implode("\r\n", $lines)."\r\n\r\nCorpo del messaggio di test.\r\n";
}

function buildDsnRawEmail(string $reportType): string
{
    $boundary = 'boundary_'.Str::random(8);

    return "From: mailer@relay.example.test\r\n"
        ."To: ticket@example.test\r\n"
        ."Subject: Undelivered Mail Returned to Sender\r\n"
        ."Content-Type: multipart/report; report-type={$reportType}; boundary=\"{$boundary}\"\r\n"
        ."\r\n"
        ."--{$boundary}\r\n"
        ."Content-Type: text/plain\r\n"
        ."\r\n"
        ."Notifica di mancato recapito.\r\n"
        ."\r\n"
        ."--{$boundary}\r\n"
        ."Content-Type: message/delivery-status\r\n"
        ."\r\n"
        ."Action: failed\r\n"
        ."\r\n"
        ."--{$boundary}--\r\n";
}

function storeClassifyFixture(string $raw, string $fromEmail = 'cliente@example.test'): EmailMessage
{
    $rawPath = Str::ulid()->toString().'.eml';

    Storage::disk('raw-emails')->put($rawPath, $raw);

    return EmailMessage::create([
        'direction' => EmailDirection::Inbound,
        'status' => EmailStatus::Parsed,
        'from_email' => $fromEmail,
        'raw_path' => $rawPath,
        'subject' => 'placeholder',
        'received_at' => now(),
    ]);
}

beforeEach(function (): void {
    Storage::fake('raw-emails');
    config(['mail_pipeline.support_address' => 'supporto@example.test']);
    config(['mail_pipeline.rate_limit.max_per_hour' => 3]);
    config(['mail_pipeline.rate_limit.max_per_day' => 10]);
});

test('un DSN (multipart/report, report-type delivery-status) è scartato e non va al ticketing', function (): void {
    $email = storeClassifyFixture(buildDsnRawEmail('delivery-status'));

    $result = ClassifyInboundEmail::run($email);

    expect($result->status)->toBe(EmailStatus::Discarded)
        ->and($result->failure_reason)->toBe(EmailDiscardReason::DeliveryStatusNotification->value);
});

test('un multipart/report con un report-type diverso da delivery-status non è trattato come DSN', function (): void {
    $email = storeClassifyFixture(buildDsnRawEmail('feedback-report'));

    $result = ClassifyInboundEmail::run($email);

    expect($result->status)->toBe(EmailStatus::Classified);
});

test('un mittente MAILER-DAEMON/postmaster/no-reply/vuoto è scartato', function (string $fromEmail): void {
    $email = storeClassifyFixture(buildRawEmail(), $fromEmail);

    $result = ClassifyInboundEmail::run($email);

    expect($result->status)->toBe(EmailStatus::Discarded)
        ->and($result->failure_reason)->toBe(EmailDiscardReason::SystemSender->value);
})->with([
    'mailer-daemon' => ['MAILER-DAEMON@example.test'],
    'postmaster' => ['postmaster@example.test'],
    'no-reply' => ['no-reply@example.test'],
    'noreply' => ['noreply@example.test'],
    'vuoto' => [''],
]);

test('un mittente normale non è scartato come mittente di sistema', function (): void {
    $email = storeClassifyFixture(buildRawEmail(), 'cliente@example.test');

    $result = ClassifyInboundEmail::run($email);

    expect($result->status)->toBe(EmailStatus::Classified);
});

test('un mittente in email_suppressions attiva è scartato', function (): void {
    EmailSuppression::create([
        'email' => 'soppresso@example.test',
        'reason' => SuppressionReason::Manual,
    ]);

    $email = storeClassifyFixture(buildRawEmail(), 'soppresso@example.test');

    $result = ClassifyInboundEmail::run($email);

    expect($result->status)->toBe(EmailStatus::Discarded)
        ->and($result->failure_reason)->toBe(EmailDiscardReason::Suppressed->value);
});

test('un mittente con soppressione scaduta non è scartato', function (): void {
    EmailSuppression::create([
        'email' => 'scaduto@example.test',
        'reason' => SuppressionReason::LoopProtection,
        'expires_at' => now()->subHour(),
    ]);

    $email = storeClassifyFixture(buildRawEmail(), 'scaduto@example.test');

    $result = ClassifyInboundEmail::run($email);

    expect($result->status)->toBe(EmailStatus::Classified);
});

test('un mittente non in email_suppressions non è scartato', function (): void {
    $email = storeClassifyFixture(buildRawEmail(), 'non-soppresso@example.test');

    $result = ClassifyInboundEmail::run($email);

    expect($result->status)->toBe(EmailStatus::Classified);
});

test('un mittente uguale all\'indirizzo della piattaforma stessa è scartato', function (): void {
    $email = storeClassifyFixture(buildRawEmail(), 'supporto@example.test');

    $result = ClassifyInboundEmail::run($email);

    expect($result->status)->toBe(EmailStatus::Discarded)
        ->and($result->failure_reason)->toBe(EmailDiscardReason::SelfSender->value);
});

test('un mittente diverso dalla piattaforma non è scartato come self-sender', function (): void {
    $email = storeClassifyFixture(buildRawEmail(), 'cliente@example.test');

    $result = ClassifyInboundEmail::run($email);

    expect($result->status)->toBe(EmailStatus::Classified);
});

test('Auto-Submitted diverso da no è scartato', function (): void {
    $email = storeClassifyFixture(buildRawEmail(['Auto-Submitted' => 'auto-replied']));

    $result = ClassifyInboundEmail::run($email);

    expect($result->status)->toBe(EmailStatus::Discarded)
        ->and($result->failure_reason)->toBe(EmailDiscardReason::AutoSubmitted->value);
});

test('Auto-Submitted: no non è scartato', function (): void {
    $email = storeClassifyFixture(buildRawEmail(['Auto-Submitted' => 'no']));

    $result = ClassifyInboundEmail::run($email);

    expect($result->status)->toBe(EmailStatus::Classified);
});

test('Precedence bulk/list/junk è scartato', function (string $precedence): void {
    $email = storeClassifyFixture(buildRawEmail(['Precedence' => $precedence]));

    $result = ClassifyInboundEmail::run($email);

    expect($result->status)->toBe(EmailStatus::Discarded)
        ->and($result->failure_reason)->toBe(EmailDiscardReason::Precedence->value);
})->with(['bulk', 'list', 'junk']);

test('Precedence assente non è scartato', function (): void {
    $email = storeClassifyFixture(buildRawEmail());

    $result = ClassifyInboundEmail::run($email);

    expect($result->status)->toBe(EmailStatus::Classified);
});

test('List-Id presente è scartato come mailing list', function (): void {
    $email = storeClassifyFixture(buildRawEmail(['List-Id' => '<annunci.example.test>']));

    $result = ClassifyInboundEmail::run($email);

    expect($result->status)->toBe(EmailStatus::Discarded)
        ->and($result->failure_reason)->toBe(EmailDiscardReason::MailingList->value);
});

test('List-Unsubscribe presente è scartato come mailing list', function (): void {
    $email = storeClassifyFixture(buildRawEmail(['List-Unsubscribe' => '<mailto:unsub@example.test>']));

    $result = ClassifyInboundEmail::run($email);

    expect($result->status)->toBe(EmailStatus::Discarded)
        ->and($result->failure_reason)->toBe(EmailDiscardReason::MailingList->value);
});

test('nessun header di mailing list non è scartato', function (): void {
    $email = storeClassifyFixture(buildRawEmail());

    $result = ClassifyInboundEmail::run($email);

    expect($result->status)->toBe(EmailStatus::Classified);
});

test('X-Auto-Response-Suppress presente è scartato', function (): void {
    $email = storeClassifyFixture(buildRawEmail(['X-Auto-Response-Suppress' => 'All']));

    $result = ClassifyInboundEmail::run($email);

    expect($result->status)->toBe(EmailStatus::Discarded)
        ->and($result->failure_reason)->toBe(EmailDiscardReason::AutoResponseSuppressed->value);
});

test('senza X-Auto-Response-Suppress non è scartato', function (): void {
    $email = storeClassifyFixture(buildRawEmail());

    $result = ClassifyInboundEmail::run($email);

    expect($result->status)->toBe(EmailStatus::Classified);
});

test('oltre la soglia oraria il messaggio è comunque classificato ma il mittente va in loop_protection', function (): void {
    $fromEmail = 'tantiinvii@example.test';

    for ($i = 0; $i < 3; $i++) {
        EmailMessage::create([
            'direction' => EmailDirection::Inbound,
            'status' => EmailStatus::Classified,
            'from_email' => $fromEmail,
            'subject' => "Messaggio {$i}",
            'received_at' => now(),
        ]);
    }

    $email = storeClassifyFixture(buildRawEmail(), $fromEmail);

    $result = ClassifyInboundEmail::run($email);

    expect($result->status)->toBe(EmailStatus::Classified);

    $suppression = EmailSuppression::query()->where('email', $fromEmail)->first();

    expect($suppression)->not->toBeNull()
        ->and($suppression->reason)->toBe(SuppressionReason::LoopProtection)
        ->and($suppression->expires_at)->not->toBeNull();
});

test('sotto la soglia oraria il mittente non va in soppressione', function (): void {
    $fromEmail = 'pochiinvii@example.test';

    EmailMessage::create([
        'direction' => EmailDirection::Inbound,
        'status' => EmailStatus::Classified,
        'from_email' => $fromEmail,
        'subject' => 'Messaggio precedente',
        'received_at' => now(),
    ]);

    $email = storeClassifyFixture(buildRawEmail(), $fromEmail);

    $result = ClassifyInboundEmail::run($email);

    expect($result->status)->toBe(EmailStatus::Classified);

    expect(EmailSuppression::query()->where('email', $fromEmail)->exists())->toBeFalse();
});

test('un file grezzo mancante non lancia un\'eccezione: il messaggio passa a failed con motivo loggato', function (): void {
    Log::spy();

    $email = EmailMessage::create([
        'direction' => EmailDirection::Inbound,
        'status' => EmailStatus::Parsed,
        'from_email' => 'cliente@example.test',
        'raw_path' => 'inesistente.eml',
        'subject' => 'placeholder',
        'received_at' => now(),
    ]);

    $result = ClassifyInboundEmail::run($email);

    expect($result->status)->toBe(EmailStatus::Failed)
        ->and($result->failure_reason)->not->toBeNull();

    Log::shouldHaveReceived('warning')->once();
});
