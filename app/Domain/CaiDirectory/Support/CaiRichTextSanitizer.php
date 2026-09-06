<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

use App\Domain\Ticketing\Support\TicketMessageSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

/**
 * Sanitizzazione dei campi HTML grezzi del datapack RUNTS-CAI (`office_hours`/`notices`
 * su `CaiSection`/`CaiSubsection`, US-802): il contenuto reale è scrappato dal sito CAI
 * e include marcatori di formattazione inline (`<span style="...">`, `<br>`) che vanno
 * preservati nel testo ma senza lo stile/i tag stessi.
 *
 * Diverso da {@see TicketMessageSanitizer}: quello scarta
 * INTERAMENTE (tag + contenuto) un elemento non in allowlist come `span` (verificato:
 * `Symfony\Component\HtmlSanitizer\HtmlSanitizer` droppa il contenuto di un elemento non
 * dichiarato, non solo il tag) — comportamento corretto per un messaggio ticket (mai
 * markup sconosciuto da un utente), ma qui perderebbe testo informativo reale scritto
 * dalla sezione (es. l'annotazione stagionale fra parentesi). Questa classe aggiunge
 * `span` all'allowlist (nessun attributo ammesso: lo stile inline viene comunque tolto)
 * proprio per questo motivo — non riusare `TicketMessageSanitizer` per questo dominio.
 */
final class CaiRichTextSanitizer
{
    /**
     * @var array<string, list<string>>
     */
    private const array ALLOWED_ELEMENTS = [
        'p' => [],
        'br' => [],
        'span' => [],
        'strong' => [],
        'b' => [],
        'em' => [],
        'i' => [],
        'u' => [],
        'ul' => [],
        'ol' => [],
        'li' => [],
        'a' => ['href', 'title'],
    ];

    public static function sanitize(string $html): string
    {
        return self::sanitizer()->sanitize($html);
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
