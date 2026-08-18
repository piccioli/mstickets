<?php

declare(strict_types=1);

namespace App\Domain\Mail\Support;

/**
 * Risultato di `App\Domain\Mail\Parsers\EmailBodyParser::parse()` (§7.3.5,
 * US-303): `bodyHtml` è già passato da `TicketMessageSanitizer::sanitize()`
 * (allowlist, mai output grezzo) e con il testo citato/firma già rimossi da
 * `QuotedTextRemover`; `bodyText` preferisce il `text/plain` originale e cade
 * sull'HTML (già ripulito/sanitizzato) solo quando il plain manca. Entrambi
 * `null` quando il messaggio non aveva contenuto testuale utile dopo la
 * rimozione del testo citato.
 */
final readonly class ParsedEmailBody
{
    public function __construct(
        public ?string $bodyText,
        public ?string $bodyHtml,
    ) {}
}
