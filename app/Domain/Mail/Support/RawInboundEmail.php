<?php

declare(strict_types=1);

namespace App\Domain\Mail\Support;

/**
 * Un messaggio grezzo letto dalla casella IMAP (§7.4 del PRD, US-301): il
 * contenuto `.eml` completo (header + corpo, non ancora parsato) più i
 * metadati IMAP necessari per l'idempotenza (`email_messages.imap_folder`/
 * `imap_uid`, unique da Fase 0/US-016). `messageId`/`fromEmail`/`fromName`/
 * `subject` sono letti dagli header di envelope già decodificati dalla
 * libreria IMAP al momento del fetch (US-302, servono per popolare le
 * colonne NOT NULL di `email_messages` prima che il parsing vero — corpo,
 * charset, normalizzazione subject — arrivi con `App\Domain\Mail\Parsers\*`,
 * US-303): nessuna logica di parsing qui.
 */
final readonly class RawInboundEmail
{
    public function __construct(
        public string $rawMessage,
        public string $imapFolder,
        public int $imapUid,
        public ?string $messageId = null,
        public ?string $fromEmail = null,
        public ?string $fromName = null,
        public ?string $subject = null,
    ) {}
}
