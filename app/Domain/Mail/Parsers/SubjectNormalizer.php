<?php

declare(strict_types=1);

namespace App\Domain\Mail\Parsers;

use App\Domain\Mail\Support\NormalizedSubject;
use Illuminate\Support\Facades\Log;

/**
 * Normalizza il subject di un'email inbound (§7.3.5, US-303): rimuove i
 * prefissi di risposta/inoltro anche in cascata e riconosce il token
 * `[#<id>]` usato dal threading via subject (US-306, fallback di livello 3;
 * scritto in uscita dalle notifiche da US-311 in poi). Il token, se presente,
 * NON viene rimosso dal subject restituito: US-306 lo cerca direttamente nel
 * subject normalizzato già salvato su `email_messages.subject`.
 *
 * `Webklex\PHPIMAP\Message::fromString()` NON decodifica gli header MIME
 * "encoded-word" (`=?UTF-8?Q?...?=`) quando l'estensione PECL `imap` non è
 * caricata (mai il caso in questo progetto: `webklex/php-imap` è stato scelto
 * proprio per non dipendere da quell'estensione, US-301) — verificato
 * empiricamente: `HeaderDecoder::mimeHeaderDecode()` senza l'estensione si
 * limita a un `convertEncoding()` sulla stringa ancora codificata, il pattern
 * `=?...?=` resta intatto. Questa classe decodifica quindi il subject grezzo
 * da sé con `mb_decode_mimeheader()` PRIMA di normalizzare i prefissi: un
 * fallimento di decoding (charset non supportato) non lancia mai
 * un'eccezione, viene solo loggato e il subject grezzo è usato come fallback.
 */
final class SubjectNormalizer
{
    private const array REPLY_FORWARD_PREFIXES = ['re', 'r', 'fwd', 'fw', 'aw', 'i', 'rif'];

    private const string TICKET_TOKEN_PATTERN = '/^\[#(\d+)\]\s*/';

    public static function normalize(?string $subject): NormalizedSubject
    {
        $subject = trim(self::decodeMimeEncodedWords((string) $subject));

        $subject = self::stripReplyForwardPrefixes($subject);

        $ticketId = null;

        if (preg_match(self::TICKET_TOKEN_PATTERN, $subject, $matches) === 1) {
            $ticketId = (int) $matches[1];
        }

        return new NormalizedSubject($subject, $ticketId);
    }

    private static function decodeMimeEncodedWords(string $subject): string
    {
        if ($subject === '' || ! str_contains($subject, '=?')) {
            return $subject;
        }

        $decoded = mb_decode_mimeheader($subject);

        if ($decoded === '' || str_contains($decoded, '=?')) {
            Log::warning('mail.parse.subject_decode_failed', ['subject' => $subject]);

            return $subject;
        }

        return $decoded;
    }

    private static function stripReplyForwardPrefixes(string $subject): string
    {
        $prefixPattern = '/^\s*(?:'.implode('|', self::REPLY_FORWARD_PREFIXES).')\s*:\s*/i';

        do {
            $before = $subject;
            $subject = preg_replace($prefixPattern, '', $subject) ?? $subject;
            $subject = ltrim($subject);
        } while ($subject !== $before && $subject !== '');

        return $subject;
    }
}
