<?php

declare(strict_types=1);

namespace App\Domain\Mail\Parsers;

use App\Domain\Mail\Support\ParsedEmailBody;
use App\Domain\Ticketing\Support\TicketMessageSanitizer;

/**
 * Estrae il corpo di un'email inbound già decodificata da
 * `Webklex\PHPIMAP\Message` (§7.3.5, US-303): preferisce sempre il
 * `text/plain` per `bodyText`, converte l'HTML in testo solo quando il plain
 * manca (mai il contrario). Il `text/html`, quando presente, è sempre
 * sanitizzato con lo stesso allowlist già usato per i messaggi web
 * (`App\Domain\Ticketing\Support\TicketMessageSanitizer`, US-106) — mai un
 * corpo HTML grezzo scritto sul record (problema 9 del v1, XSS stored). Il
 * testo citato/firma è rimosso da `QuotedTextRemover` PRIMA di sanitizzare/
 * derivare il testo, cosicché la versione testo derivata dall'HTML non
 * ripeschi mai una citazione già scartata dal ramo plain.
 */
final class EmailBodyParser
{
    public static function parse(?string $textPlain, ?string $textHtml): ParsedEmailBody
    {
        $bodyHtml = self::cleanHtml($textHtml);
        $bodyText = self::resolveText($textPlain, $bodyHtml);

        return new ParsedEmailBody(bodyText: $bodyText, bodyHtml: $bodyHtml);
    }

    private static function cleanHtml(?string $html): ?string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return null;
        }

        $withoutQuotes = QuotedTextRemover::stripHtml($html);
        $sanitized = trim(TicketMessageSanitizer::sanitize($withoutQuotes));

        return $sanitized !== '' ? $sanitized : null;
    }

    private static function resolveText(?string $textPlain, ?string $sanitizedHtml): ?string
    {
        $textPlain = trim((string) $textPlain);

        $text = $textPlain !== ''
            ? $textPlain
            : ($sanitizedHtml !== null ? TicketMessageSanitizer::toPlainText($sanitizedHtml) : '');

        $stripped = QuotedTextRemover::strip($text);

        return $stripped !== '' ? $stripped : null;
    }
}
