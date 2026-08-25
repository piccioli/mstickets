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
 * US-303): nessuna logica di parsing qui. `to`/`inReplyTo`/`references` sono
 * letti dagli stessi header (US-306, risoluzione thread): `to` alimenta il
 * match VERP (livello 1, token `ticket+<ulid>` nel destinatario), `inReplyTo`/
 * `references` alimentano il match per citazione (livello 2).
 */
final readonly class RawInboundEmail
{
    /**
     * @param  array<int, string>  $to
     * @param  array<int, string>  $references
     */
    public function __construct(
        public string $rawMessage,
        public string $imapFolder,
        public int $imapUid,
        public ?string $messageId = null,
        public ?string $fromEmail = null,
        public ?string $fromName = null,
        public ?string $subject = null,
        public array $to = [],
        public ?string $inReplyTo = null,
        public array $references = [],
    ) {}
}
