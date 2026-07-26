<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Support;

/**
 * Unica fonte di verità per i tipi di allegato ammessi sui messaggi del ticket
 * (§17.2 del PRD): legge `config/ticketing.php` (valori da env), riusabile sia
 * dall'Action di upload di questa fase (US-107) sia da un futuro parser email
 * inbound (Fase 3), senza duplicare la lista in un secondo punto.
 */
final class TicketAttachmentTypes
{
    /**
     * @return list<string>
     */
    public static function allowedExtensions(): array
    {
        return array_values(array_unique([
            ...(array) config('ticketing.attachments.documents.extensions'),
            ...(array) config('ticketing.attachments.images.extensions'),
            ...(array) config('ticketing.attachments.audio.extensions'),
        ]));
    }

    /**
     * @return list<string>
     */
    public static function allowedMimeTypes(): array
    {
        return array_values(array_unique([
            ...(array) config('ticketing.attachments.documents.mimes'),
            ...(array) config('ticketing.attachments.images.mimes'),
            ...(array) config('ticketing.attachments.audio.mimes'),
        ]));
    }

    public static function maxFileSize(): int
    {
        return (int) config('ticketing.attachments.max_file_size');
    }

    public static function disk(): string
    {
        return (string) config('ticketing.attachments.disk');
    }

    public static function isAllowed(string $extension, string $mimeType): bool
    {
        return in_array(strtolower($extension), self::allowedExtensions(), true)
            && in_array(strtolower($mimeType), self::allowedMimeTypes(), true);
    }
}
