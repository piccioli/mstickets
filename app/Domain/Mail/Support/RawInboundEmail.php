<?php

declare(strict_types=1);

namespace App\Domain\Mail\Support;

/**
 * Un messaggio grezzo letto dalla casella IMAP (§7.4 del PRD, US-301): il
 * contenuto `.eml` completo (header + corpo, non ancora parsato) più i
 * metadati IMAP necessari per l'idempotenza (`email_messages.imap_folder`/
 * `imap_uid`, unique da Fase 0/US-016). Nessuna logica qui: il parsing vero
 * arriva con `App\Domain\Mail\Parsers\*` (US-303).
 */
final readonly class RawInboundEmail
{
    public function __construct(
        public string $rawMessage,
        public string $imapFolder,
        public int $imapUid,
    ) {}
}
