<?php

declare(strict_types=1);

namespace App\Import\Parsers;

use Illuminate\Support\Carbon;
use Throwable;

/**
 * Scompone `stories.customer_request` (§11.5 stage 13 del PRD) nei singoli messaggi
 * della conversazione. Il v1 **prepende** ogni risposta come un blocco fisso generato
 * dall'applicazione:
 *
 *   "<Autore> ha risposto il: DD-MM-YYYY HH:MM\n <div style='background-color:
 *   #XXXXXX; border-left: 4px solid #YYYYYY; padding: 10px 20px;'>...corpo...</div>
 *   <div style='height: 2px; background-color: #e2e8f0; margin: 20px 0;'></div>"
 *
 * seguito, alla fine della stringa, dal contenuto originale (il testo con cui il
 * ticket è stato creato, mai a sua volta avvolto in un blocco). Solo QUESTO template
 * esatto viene riconosciuto come "blocco di risposta": qualunque altra forma di
 * conversazione accumulata nel v1 (email inoltrate in chiaro con intestazioni
 * "Da:"/"Date:", citazioni Gmail annidate in `<blockquote>`, notifiche di form, ecc.)
 * NON viene scomposta — resta un unico messaggio "originale" con l'HTML integrale,
 * che è esattamente il comportamento di fallback richiesto dall'AC quando il parsing
 * non è affidabile: non c'è una modalità di "fallimento" distinta, zero blocchi
 * riconosciuti equivale a un unico blocco.
 *
 * Ordine di ritorno: cronologico crescente (il v1 prepende, il v2 è cronologico) —
 * il contenuto originale, se presente, è sempre il più vecchio e viene per primo.
 */
final class CustomerRequestParser
{
    private const string REPLY_BLOCK_PATTERN = <<<'REGEX'
        /^\s*(?P<author>[^\n<]{1,150}?)\s+ha risposto il:\s*(?P<day>\d{2})-(?P<month>\d{2})-(?P<year>\d{4})\s+(?P<hour>\d{2}):(?P<minute>\d{2})\s*<div\s+style='background-color:\s*#[0-9a-fA-F]{6};\s*border-left:\s*4px solid\s*#[0-9a-fA-F]{6};\s*padding:\s*10px 20px;'>(?P<body>.*?)<\/div><div\s+style='height:\s*2px;\s*background-color:\s*#[0-9a-fA-F]{6};\s*margin:\s*20px 0;'><\/div>/s
        REGEX;

    /**
     * @return list<ParsedTicketMessage>
     */
    public static function parse(string $html): array
    {
        $remaining = $html;
        $blocks = [];

        while (preg_match(self::REPLY_BLOCK_PATTERN, $remaining, $matches) === 1) {
            $blocks[] = $matches;
            $remaining = substr($remaining, strlen($matches[0]));
        }

        $messages = [];

        foreach (array_reverse($blocks) as $match) {
            $messages[] = new ParsedTicketMessage(
                author: trim($match['author']),
                postedAt: self::parseTimestamp($match),
                body: trim($match['body']),
                isOriginal: false,
            );
        }

        $original = trim($remaining);

        if ($original !== '') {
            array_unshift($messages, new ParsedTicketMessage(
                author: null,
                postedAt: null,
                body: $original,
                isOriginal: true,
            ));
        }

        return $messages;
    }

    /**
     * @param  array<int|string, string>  $match
     */
    private static function parseTimestamp(array $match): ?Carbon
    {
        $day = (int) $match['day'];
        $month = (int) $match['month'];
        $year = (int) $match['year'];

        // checkdate() prima di Carbon::create(): quest'ultimo non lancia un'eccezione
        // per un giorno fuori range (es. 31 aprile), lo trabocca silenziosamente al
        // mese successivo — un valore plausibile ma sbagliato, peggio di un null
        // esplicito che attiva la distribuzione monotona richiesta dall'AC.
        if (! checkdate($month, $day, $year)) {
            return null;
        }

        try {
            return Carbon::create($year, $month, $day, (int) $match['hour'], (int) $match['minute']);
        } catch (Throwable) {
            return null;
        }
    }
}
