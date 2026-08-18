<?php

declare(strict_types=1);

namespace App\Domain\Mail\Parsers;

use DOMDocument;
use DOMXPath;

/**
 * Rimuove testo citato e firme da un corpo email (§7.3.5, US-303, problema 8
 * del v1: mai una regex generica non testata tipo
 * `preg_replace('/---.*?---/s', ...)`). Il testo rimosso non è perso: il
 * `.eml` grezzo resta archiviato da US-302, questa rimozione riguarda solo il
 * corpo scritto su `email_messages.body_text`/`body_html`.
 *
 * `strip()` opera riga per riga sul testo semplice e taglia al primo indizio
 * di citazione/firma trovato (tutto ciò che segue è considerato citato).
 * `stripHtml()` rimuove i `<blockquote>` (il modo in cui Gmail/Outlook
 * web/Apple Mail marcano semanticamente il testo citato in HTML) via
 * DOMDocument, non con una regex sui tag.
 */
final class QuotedTextRemover
{
    public static function strip(string $text): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];

        $cutAt = null;

        foreach ($lines as $index => $line) {
            if (self::isCutLine($line) || self::isOutlookHeaderBlockStart($lines, $index)) {
                $cutAt = $index;
                break;
            }
        }

        $kept = $cutAt === null ? $lines : array_slice($lines, 0, $cutAt);

        return trim(implode("\n", $kept));
    }

    public static function stripHtml(string $html): string
    {
        if (trim($html) === '') {
            return $html;
        }

        $document = new DOMDocument;

        libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8">'.$html,
            LIBXML_NOERROR | LIBXML_NOWARNING,
        );
        libxml_clear_errors();

        $xpath = new DOMXPath($document);

        foreach (iterator_to_array($xpath->query('//blockquote') ?: []) as $node) {
            $node->parentNode?->removeChild($node);
        }

        $body = $document->getElementsByTagName('body')->item(0);

        return $body !== null ? trim($document->saveHTML($body) ?: '') : trim($document->saveHTML() ?: '');
    }

    private static function isCutLine(string $line): bool
    {
        $trimmed = trim($line);

        if ($trimmed === '--' || $trimmed === '-- ') {
            return true;
        }

        foreach ([
            '/^-{2,}\s*original message\s*-{2,}$/i',
            '/^on\s.+\swrote:\s*$/i',
            '/^il\s.+\sha scritto:\s*$/i',
            '/^>/',
        ] as $pattern) {
            if (preg_match($pattern, $trimmed) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $lines
     */
    private static function isOutlookHeaderBlockStart(array $lines, int $index): bool
    {
        $line = trim($lines[$index]);

        if (preg_match('/^(from|da):\s*\S/i', $line) !== 1) {
            return false;
        }

        for ($i = $index + 1; $i < min($index + 5, count($lines)); $i++) {
            if (preg_match('/^(sent|inviato|to|a|subject|oggetto):\s*/i', trim($lines[$i])) === 1) {
                return true;
            }
        }

        return false;
    }
}
