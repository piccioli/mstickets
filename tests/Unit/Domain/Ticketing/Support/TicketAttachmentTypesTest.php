<?php

declare(strict_types=1);

use App\Domain\Ticketing\Support\TicketAttachmentTypes;

test('allowed extensions merge documents, images and audio from config', function (): void {
    $extensions = TicketAttachmentTypes::allowedExtensions();

    expect($extensions)->toContain('pdf', 'jpg', 'svg', 'mp3', 'mp4');
});

test('allowed mime types merge documents, images and audio from config', function (): void {
    $mimes = TicketAttachmentTypes::allowedMimeTypes();

    expect($mimes)->toContain('application/pdf', 'image/svg+xml', 'audio/mpeg', 'video/mp4');
});

test('max file size defaults to 10 MB', function (): void {
    expect(TicketAttachmentTypes::maxFileSize())->toBe(10 * 1024 * 1024);
});

test('disk defaults to the private ticket-attachments disk', function (): void {
    expect(TicketAttachmentTypes::disk())->toBe('ticket-attachments');
});

test('isAllowed accepts a known extension and mime pair', function (): void {
    expect(TicketAttachmentTypes::isAllowed('pdf', 'application/pdf'))->toBeTrue()
        ->and(TicketAttachmentTypes::isAllowed('PDF', 'APPLICATION/PDF'))->toBeTrue();
});

test('isAllowed rejects an extension or mime not in the shared list', function (): void {
    expect(TicketAttachmentTypes::isAllowed('exe', 'application/x-msdownload'))->toBeFalse()
        ->and(TicketAttachmentTypes::isAllowed('pdf', 'application/x-msdownload'))->toBeFalse();
});
