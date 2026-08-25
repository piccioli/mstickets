<?php

declare(strict_types=1);

namespace App\Domain\Mail\Support;

use App\Domain\Ticketing\Support\TicketAttachmentTypes;

/**
 * Unica fonte di verità per i limiti/tipi ammessi sugli allegati inbound
 * (§7.3.9 del PRD, US-309): legge `config/mail_pipeline.php`, mai
 * `config/ticketing.php` — il contesto email è deliberatamente più
 * permissivo di {@see TicketAttachmentTypes}
 * (US-107), quindi una configurazione propria e distinta, non condivisa.
 */
final class EmailAttachmentTypes
{
    public static function maxFileSize(): int
    {
        return (int) config('mail_pipeline.attachments.max_file_size');
    }

    public static function maxTotalSize(): int
    {
        return (int) config('mail_pipeline.attachments.max_total_size');
    }

    public static function maxCount(): int
    {
        return (int) config('mail_pipeline.attachments.max_count');
    }

    public static function includeInline(): bool
    {
        return (bool) config('mail_pipeline.attachments.include_inline');
    }

    /**
     * @return list<string>
     */
    public static function allowedExtensions(): array
    {
        return array_values(array_map(
            strtolower(...),
            (array) config('mail_pipeline.attachments.allowed_extensions'),
        ));
    }

    /**
     * @return list<string>
     */
    public static function allowedMimeTypes(): array
    {
        return array_values(array_map(
            strtolower(...),
            (array) config('mail_pipeline.attachments.allowed_mimes'),
        ));
    }

    public static function isAllowed(string $extension, string $mimeType): bool
    {
        return in_array(strtolower($extension), self::allowedExtensions(), true)
            && in_array(strtolower($mimeType), self::allowedMimeTypes(), true);
    }
}
