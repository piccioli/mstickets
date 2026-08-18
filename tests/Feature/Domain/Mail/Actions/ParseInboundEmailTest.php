<?php

declare(strict_types=1);

use App\Domain\Mail\Actions\ParseInboundEmail;
use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Models\EmailMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function storeRawFixtureAsReceived(string $filename, array $overrides = []): EmailMessage
{
    $raw = file_get_contents(base_path("tests/Fixtures/emails/{$filename}"))
        ?: throw new RuntimeException("Fixture .eml mancante: {$filename}");

    $rawPath = Str::ulid()->toString().'.eml';

    Storage::disk('raw-emails')->put($rawPath, $raw);

    return EmailMessage::create(array_merge([
        'direction' => EmailDirection::Inbound,
        'status' => EmailStatus::Received,
        'from_email' => 'cliente@example.test',
        'raw_path' => $rawPath,
        'subject' => 'placeholder',
    ], $overrides));
}

test('parsa subject e corpo di un\'email solo testo e passa a status parsed', function (): void {
    Storage::fake('raw-emails');

    $email = storeRawFixtureAsReceived('richiesta-supporto.eml', ['subject' => 'Non riesco ad accedere al portale']);

    $result = ParseInboundEmail::run($email);

    expect($result->status)->toBe(EmailStatus::Parsed)
        ->and($result->subject)->toBe('Non riesco ad accedere al portale')
        ->and($result->body_text)->toContain('non riesco più ad accedere al portale')
        ->and($result->body_html)->toBeNull();
});

test('preferisce il text/plain quando un\'email ha entrambi i corpi', function (): void {
    Storage::fake('raw-emails');

    $email = storeRawFixtureAsReceived('richiesta-html-e-plain.eml', ['subject' => 'Problema con la stampante']);

    $result = ParseInboundEmail::run($email);

    expect($result->status)->toBe(EmailStatus::Parsed)
        ->and($result->body_text)->toContain('la stampante')
        ->and($result->body_html)->toContain('<p>')
        ->and($result->body_html)->not->toContain('script')
        ->and($result->body_html)->not->toContain('ha scritto');
});

test('deriva il testo dall\'HTML e rimuove la citazione quando manca il plain', function (): void {
    Storage::fake('raw-emails');

    $email = storeRawFixtureAsReceived('risposta-solo-html.eml', ['subject' => 'Re: [#42] Richiesta assistenza']);

    $result = ParseInboundEmail::run($email);

    expect($result->status)->toBe(EmailStatus::Parsed)
        ->and($result->body_text)->toBe('Confermo che il problema persiste anche oggi.')
        ->and($result->body_html)->not->toContain('script')
        ->and($result->body_html)->not->toContain('Grazie per la segnalazione');
});

test('normalizza il subject e rimuove testo citato/firma da una risposta in plain text', function (): void {
    Storage::fake('raw-emails');

    $email = storeRawFixtureAsReceived('risposta-con-citazione.eml', ['subject' => 'Re: [#42] Richiesta assistenza']);

    $result = ParseInboundEmail::run($email);

    expect($result->status)->toBe(EmailStatus::Parsed)
        ->and($result->subject)->toBe('[#42] Richiesta assistenza')
        ->and($result->body_text)->toBe('Confermo che il problema persiste anche oggi.');
});

test('decodifica un subject con encoded-word RFC 2047', function (): void {
    Storage::fake('raw-emails');

    $email = storeRawFixtureAsReceived('richiesta-subject-accentato.eml', ['subject' => 'placeholder']);

    $result = ParseInboundEmail::run($email);

    expect($result->status)->toBe(EmailStatus::Parsed)
        ->and($result->subject)->toBe('Problema con caffè');
});

test('un file grezzo mancante non lancia un\'eccezione: il messaggio passa a failed con motivo loggato', function (): void {
    Storage::fake('raw-emails');
    Log::spy();

    $email = EmailMessage::create([
        'direction' => EmailDirection::Inbound,
        'status' => EmailStatus::Received,
        'from_email' => 'cliente@example.test',
        'raw_path' => 'inesistente.eml',
        'subject' => 'placeholder',
    ]);

    $result = ParseInboundEmail::run($email);

    expect($result->status)->toBe(EmailStatus::Failed)
        ->and($result->failure_reason)->not->toBeNull();

    Log::shouldHaveReceived('warning')->once();
});
