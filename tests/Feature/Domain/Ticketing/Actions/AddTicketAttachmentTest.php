<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Actions\AddTicketAttachment;
use App\Domain\Ticketing\Enums\TicketLogEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('stores an allowed file on the private disk and returns the media', function (): void {
    Storage::fake('ticket-attachments');

    $author = User::factory()->create();
    $message = ticketMessage();
    $file = UploadedFile::fake()->image('photo.jpg', 10, 10);

    $media = AddTicketAttachment::run($message, $file, $author);

    expect($media->disk)->toBe('ticket-attachments')
        ->and($media->collection_name)->toBe('attachments')
        ->and($media->mime_type)->toBe('image/jpeg');

    Storage::disk('ticket-attachments')->assertExists($media->getPathRelativeToRoot());
});

test('rejects a file whose extension is not in the shared allowed list', function (): void {
    Storage::fake('ticket-attachments');

    $author = User::factory()->create();
    $message = ticketMessage();
    $file = UploadedFile::fake()->create('malware.exe', 10);

    expect(fn () => AddTicketAttachment::run($message, $file, $author))
        ->toThrow(ValidationException::class);

    expect($message->fresh()->getMedia('attachments'))->toHaveCount(0);
});

test('rejects a file larger than the configured maximum size', function (): void {
    Storage::fake('ticket-attachments');

    $author = User::factory()->create();
    $message = ticketMessage();
    $file = UploadedFile::fake()->image('big.jpg')->size(11 * 1024);

    expect(fn () => AddTicketAttachment::run($message, $file, $author))
        ->toThrow(ValidationException::class);

    expect($message->fresh()->getMedia('attachments'))->toHaveCount(0);
});

test('writes a dedicated attachment_added ticket_log', function (): void {
    Storage::fake('ticket-attachments');

    $author = User::factory()->create();
    $message = ticketMessage();
    $file = UploadedFile::fake()->image('photo.jpg', 10, 10);

    $media = AddTicketAttachment::run($message, $file, $author);

    $log = $message->ticket->logs()->sole();

    expect($log->event)->toBe(TicketLogEvent::AttachmentAdded)
        ->and($log->user_id)->toBe($author->id)
        ->and($log->changes)->toBe([
            'attachment' => ['action' => 'added', 'file_name' => $media->file_name],
        ]);
});
