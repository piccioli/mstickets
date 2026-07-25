<?php

declare(strict_types=1);

use App\Domain\Mail\Enums\EmailAttachmentStatus;
use App\Domain\Mail\Enums\EmailDirection;
use App\Domain\Mail\Enums\EmailStatus;
use App\Domain\Mail\Models\EmailAttachment;
use App\Domain\Mail\Models\EmailMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function makeEmailMessageForAttachment(): EmailMessage
{
    return EmailMessage::create([
        'direction' => EmailDirection::Inbound,
        'from_email' => 'mittente@example.com',
        'status' => EmailStatus::Received,
    ]);
}

test('email_attachments table has the columns required by §5.2', function (): void {
    expect(Schema::hasColumns('email_attachments', [
        'id', 'email_message_id', 'filename', 'mime_type', 'size_bytes', 'disk', 'path',
        'media_id', 'status', 'rejection_reason', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

test('status is cast to its backed enum', function (): void {
    $attachment = EmailAttachment::create([
        'email_message_id' => makeEmailMessageForAttachment()->id,
        'filename' => 'documento.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 1024,
        'disk' => 'local',
        'path' => 'email-attachments/documento.pdf',
        'status' => EmailAttachmentStatus::Stored,
    ]);

    expect($attachment->fresh()->status)->toBe(EmailAttachmentStatus::Stored);
});

test('deleting the email message cascades to its attachments', function (): void {
    $message = makeEmailMessageForAttachment();
    $attachment = EmailAttachment::create([
        'email_message_id' => $message->id,
        'filename' => 'documento.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 1024,
        'disk' => 'local',
        'path' => 'email-attachments/documento.pdf',
        'status' => EmailAttachmentStatus::Stored,
    ]);

    $message->delete();

    expect(EmailAttachment::find($attachment->id))->toBeNull();
});

test('belongs to an email message', function (): void {
    $message = makeEmailMessageForAttachment();
    $attachment = EmailAttachment::create([
        'email_message_id' => $message->id,
        'filename' => 'documento.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 1024,
        'disk' => 'local',
        'path' => 'email-attachments/documento.pdf',
        'status' => EmailAttachmentStatus::Stored,
    ]);

    expect($attachment->emailMessage->is($message))->toBeTrue();
});
