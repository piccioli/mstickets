<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Mail\Actions\ImportInboundEmailAttachments;
use App\Domain\Mail\Enums\EmailAttachmentStatus;
use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Models\EmailAttachment;
use App\Domain\Mail\Models\EmailMessage;
use App\Domain\Ticketing\Actions\PostTicketMessage;
use App\Domain\Ticketing\Enums\TicketMessageChannel;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Enums\TicketType;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function storeAttachmentFixtureAsEmailMessage(string $filename = 'richiesta-con-allegati.eml'): EmailMessage
{
    $raw = file_get_contents(base_path("tests/Fixtures/emails/{$filename}"))
        ?: throw new RuntimeException("Fixture .eml mancante: {$filename}");

    $rawPath = Str::ulid()->toString().'.eml';

    Storage::disk('raw-emails')->put($rawPath, $raw);

    return EmailMessage::create([
        'direction' => EmailDirection::Inbound,
        'status' => EmailStatus::Classified,
        'from_email' => 'cliente@example.test',
        'raw_path' => $rawPath,
        'subject' => 'Richiesta con allegato',
    ]);
}

function makeTicketMessageForAttachments(): TicketMessage
{
    $requester = User::factory()->create();

    $ticket = Ticket::create([
        'title' => 'Ticket con allegati',
        'status' => TicketStatus::Todo,
        'status_changed_at' => now(),
        'requester_id' => $requester->id,
        'type' => TicketType::Helpdesk,
    ]);

    return PostTicketMessage::run($ticket, $requester, '<p>corpo</p>', TicketMessageChannel::Email);
}

test('importa un allegato regolare nella collection attachments del ticket_message con record stored', function (): void {
    Storage::fake('raw-emails');
    Storage::fake('ticket-attachments');

    $email = storeAttachmentFixtureAsEmailMessage();
    $message = makeTicketMessageForAttachments();

    ImportInboundEmailAttachments::run($email, $message);

    $documento = EmailAttachment::query()->where('email_message_id', $email->id)->where('filename', 'documento.pdf')->first();

    expect($documento)->not->toBeNull()
        ->and($documento->status)->toBe(EmailAttachmentStatus::Stored)
        ->and($documento->mime_type)->toBe('application/pdf')
        ->and($documento->size_bytes)->toBe(76)
        ->and($documento->media_id)->not->toBeNull()
        ->and($documento->disk)->toBe('ticket-attachments');

    expect($message->fresh()->getMedia('attachments'))->toHaveCount(2);
});

test('gli allegati inline sono esclusi per default, nessun record creato', function (): void {
    Storage::fake('raw-emails');
    Storage::fake('ticket-attachments');

    $email = storeAttachmentFixtureAsEmailMessage();
    $message = makeTicketMessageForAttachments();

    ImportInboundEmailAttachments::run($email, $message);

    expect(EmailAttachment::query()->where('email_message_id', $email->id)->where('filename', 'logo.png')->exists())->toBeFalse();
});

test('il flag include_inline forza l\'importazione degli allegati inline', function (): void {
    Storage::fake('raw-emails');
    Storage::fake('ticket-attachments');
    config(['mail_pipeline.attachments.include_inline' => true]);

    $email = storeAttachmentFixtureAsEmailMessage();
    $message = makeTicketMessageForAttachments();

    ImportInboundEmailAttachments::run($email, $message);

    $logo = EmailAttachment::query()->where('email_message_id', $email->id)->where('filename', 'logo.png')->first();

    expect($logo)->not->toBeNull()
        ->and($logo->status)->toBe(EmailAttachmentStatus::Stored)
        ->and($message->fresh()->getMedia('attachments'))->toHaveCount(3);
});

test('un allegato di tipo non consentito produce un record rejected_mime, senza fallire gli altri', function (): void {
    Storage::fake('raw-emails');
    Storage::fake('ticket-attachments');
    config(['mail_pipeline.attachments.allowed_mimes' => ['text/plain']]);
    config(['mail_pipeline.attachments.allowed_extensions' => ['txt']]);

    $email = storeAttachmentFixtureAsEmailMessage();
    $message = makeTicketMessageForAttachments();

    ImportInboundEmailAttachments::run($email, $message);

    $documento = EmailAttachment::query()->where('email_message_id', $email->id)->where('filename', 'documento.pdf')->first();
    $nota = EmailAttachment::query()->where('email_message_id', $email->id)->where('filename', 'nota.txt')->first();

    expect($documento->status)->toBe(EmailAttachmentStatus::RejectedMime)
        ->and($documento->media_id)->toBeNull()
        ->and($documento->rejection_reason)->not->toBeNull()
        ->and($nota->status)->toBe(EmailAttachmentStatus::Stored);
});

test('un allegato più grande del limite per singolo file produce un record rejected_size, senza fallire gli altri', function (): void {
    Storage::fake('raw-emails');
    Storage::fake('ticket-attachments');
    config(['mail_pipeline.attachments.max_file_size' => 50]);

    $email = storeAttachmentFixtureAsEmailMessage();
    $message = makeTicketMessageForAttachments();

    ImportInboundEmailAttachments::run($email, $message);

    $documento = EmailAttachment::query()->where('email_message_id', $email->id)->where('filename', 'documento.pdf')->first();
    $nota = EmailAttachment::query()->where('email_message_id', $email->id)->where('filename', 'nota.txt')->first();

    expect($documento->status)->toBe(EmailAttachmentStatus::RejectedSize)
        ->and($nota->status)->toBe(EmailAttachmentStatus::Stored);
});

test('il limite di dimensione totale per messaggio viene rispettato', function (): void {
    Storage::fake('raw-emails');
    Storage::fake('ticket-attachments');
    config(['mail_pipeline.attachments.max_total_size' => 80]);

    $email = storeAttachmentFixtureAsEmailMessage();
    $message = makeTicketMessageForAttachments();

    ImportInboundEmailAttachments::run($email, $message);

    $documento = EmailAttachment::query()->where('email_message_id', $email->id)->where('filename', 'documento.pdf')->first();
    $nota = EmailAttachment::query()->where('email_message_id', $email->id)->where('filename', 'nota.txt')->first();

    expect($documento->status)->toBe(EmailAttachmentStatus::Stored)
        ->and($nota->status)->toBe(EmailAttachmentStatus::RejectedSize);
});

test('il numero massimo di allegati per messaggio viene rispettato', function (): void {
    Storage::fake('raw-emails');
    Storage::fake('ticket-attachments');
    config(['mail_pipeline.attachments.max_count' => 1]);

    $email = storeAttachmentFixtureAsEmailMessage();
    $message = makeTicketMessageForAttachments();

    ImportInboundEmailAttachments::run($email, $message);

    $documento = EmailAttachment::query()->where('email_message_id', $email->id)->where('filename', 'documento.pdf')->first();
    $nota = EmailAttachment::query()->where('email_message_id', $email->id)->where('filename', 'nota.txt')->first();

    expect($documento->status)->toBe(EmailAttachmentStatus::Stored)
        ->and($nota->status)->toBe(EmailAttachmentStatus::RejectedSize);
});

test('un errore nel salvataggio di un singolo allegato produce un record failed, senza fermare gli altri né l\'elaborazione', function (): void {
    Storage::fake('raw-emails');
    Storage::fake('ticket-attachments');
    config(['ticketing.attachments.disk' => 'disco-inesistente-per-test']);

    $email = storeAttachmentFixtureAsEmailMessage();
    $message = makeTicketMessageForAttachments();

    ImportInboundEmailAttachments::run($email, $message);

    $documento = EmailAttachment::query()->where('email_message_id', $email->id)->where('filename', 'documento.pdf')->first();
    $nota = EmailAttachment::query()->where('email_message_id', $email->id)->where('filename', 'nota.txt')->first();

    expect($documento->status)->toBe(EmailAttachmentStatus::Failed)
        ->and($documento->rejection_reason)->not->toBeNull()
        ->and($nota->status)->toBe(EmailAttachmentStatus::Failed);
});

test('nessun allegato non produce nessun record e nessuna eccezione', function (): void {
    Storage::fake('raw-emails');
    Storage::fake('ticket-attachments');

    $email = storeAttachmentFixtureAsEmailMessage('richiesta-supporto.eml');
    $message = makeTicketMessageForAttachments();

    ImportInboundEmailAttachments::run($email, $message);

    expect(EmailAttachment::query()->where('email_message_id', $email->id)->count())->toBe(0);
});

test('un raw_path mancante viene ignorato senza eccezioni, nessun allegato importato', function (): void {
    Storage::fake('raw-emails');
    Storage::fake('ticket-attachments');

    $email = EmailMessage::create([
        'direction' => EmailDirection::Inbound,
        'status' => EmailStatus::Classified,
        'from_email' => 'cliente@example.test',
        'subject' => 'Senza raw_path',
    ]);
    $message = makeTicketMessageForAttachments();

    ImportInboundEmailAttachments::run($email, $message);

    expect(EmailAttachment::query()->where('email_message_id', $email->id)->count())->toBe(0);
});
