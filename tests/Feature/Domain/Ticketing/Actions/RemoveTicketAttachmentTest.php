<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Actions\AddTicketAttachment;
use App\Domain\Ticketing\Actions\RemoveTicketAttachment;
use App\Domain\Ticketing\Enums\TicketLogEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

test('deletes the media and writes a dedicated attachment_removed ticket_log', function (): void {
    Storage::fake('ticket-attachments');

    $author = User::factory()->create();
    $message = ticketMessage();
    $media = AddTicketAttachment::run($message, UploadedFile::fake()->image('photo.jpg', 10, 10), $author);
    $fileName = $media->file_name;
    $path = $media->getPathRelativeToRoot();

    RemoveTicketAttachment::run($message->fresh(), $media, $author);

    Storage::disk('ticket-attachments')->assertMissing($path);

    expect($message->fresh()->getMedia('attachments'))->toHaveCount(0);

    $log = $message->ticket->logs()->where('event', TicketLogEvent::AttachmentRemoved)->sole();

    expect($log->user_id)->toBe($author->id)
        ->and($log->changes)->toBe([
            'attachment' => ['action' => 'removed', 'file_name' => $fileName],
        ]);
});

test('refuses to remove a media that does not belong to the given ticket message', function (): void {
    Storage::fake('ticket-attachments');

    $author = User::factory()->create();
    $message = ticketMessage();
    $otherMessage = ticketMessage();
    $media = AddTicketAttachment::run($message, UploadedFile::fake()->image('photo.jpg', 10, 10), $author);

    expect(fn () => RemoveTicketAttachment::run($otherMessage, $media, $author))
        ->toThrow(ValidationException::class);

    expect($message->fresh()->getMedia('attachments'))->toHaveCount(1);
});
