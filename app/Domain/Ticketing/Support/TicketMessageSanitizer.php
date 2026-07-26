<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

/**
 * Sanitizzazione del corpo HTML dei messaggi di conversazione (§6.1.7, §8.7): un
 * allowlist esplicito di elementi/attributi di formattazione testo, non un blocklist,
 * così che qualunque tag non elencato — incluso `<script>` e il suo contenuto —
 * venga rimosso interamente. Nessun punto del codice deve stampare `body_html` con
 * `{!! !!}` prima di essere passato da qui: l'output di {@see self::sanitize()} è già
 * sicuro per l'inserimento diretto nel DOM.
 */
final class TicketMessageSanitizer
{
    /**
     * @var array<string, list<string>>
     */
    private const array ALLOWED_ELEMENTS = [
        'p' => [],
        'br' => [],
        'strong' => [],
        'b' => [],
        'em' => [],
        'i' => [],
        'u' => [],
        'ul' => [],
        'ol' => [],
        'li' => [],
        'blockquote' => [],
        'code' => [],
        'pre' => [],
        'a' => ['href', 'title'],
    ];

    public static function sanitize(string $html): string
    {
        return self::sanitizer()->sanitize($html);
    }

    /**
     * Versione testuale del messaggio (§6.1.7), derivata dal corpo GIÀ sanitizzato:
     * mai dal corpo grezzo, altrimenti un tag rimosso dall'allowlist potrebbe comunque
     * lasciare tracce del proprio contenuto nella versione testo.
     */
    public static function toPlainText(string $sanitizedHtml): string
    {
        return trim(html_entity_decode(strip_tags($sanitizedHtml), ENT_QUOTES, 'UTF-8'));
    }

    private static function sanitizer(): HtmlSanitizerInterface
    {
        static $sanitizer = null;

        if ($sanitizer !== null) {
            return $sanitizer;
        }

        $config = new HtmlSanitizerConfig;

        foreach (self::ALLOWED_ELEMENTS as $element => $attributes) {
            $config = $config->allowElement($element, $attributes);
        }

        $config = $config->allowLinkSchemes(['http', 'https', 'mailto']);

        return $sanitizer = new HtmlSanitizer($config);
    }
}
