<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Sanitizzazione allowlist di un SVG prima di essere servito (§17.2 nota del PRD: un
 * SVG può contenere `<script>`, gestori `on*` o riferimenti `javascript:`). Stesso
 * principio allowlist di {@see TicketMessageSanitizer}, ma su un documento XML, non
 * HTML: `symfony/html-sanitizer` non normalizza tag/namespace SVG, serve un
 * sanitizzatore dedicato. Ogni elemento non in allowlist viene rimosso INSIEME al suo
 * contenuto (coerente con `<script>`); `href`/`xlink:href` sono ammessi solo se
 * puntano a un frammento interno (`#id`), mai a una risorsa esterna.
 */
final class TicketAttachmentSvgSanitizer
{
    private const array ALLOWED_ELEMENTS = [
        'svg', 'g', 'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon',
        'text', 'tspan', 'defs', 'use', 'title', 'desc',
        'linearGradient', 'radialGradient', 'stop', 'clipPath',
    ];

    private const array ALLOWED_ATTRIBUTES = [
        'id', 'class', 'style', 'd', 'x', 'y', 'x1', 'y1', 'x2', 'y2', 'cx', 'cy', 'r', 'rx', 'ry',
        'width', 'height', 'viewBox', 'preserveAspectRatio', 'transform', 'points',
        'fill', 'fill-opacity', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin',
        'opacity', 'offset', 'stop-color', 'stop-opacity', 'gradientUnits', 'gradientTransform',
        'clip-path', 'xmlns', 'xmlns:xlink', 'version',
    ];

    private const array URL_ATTRIBUTES = ['href', 'xlink:href'];

    public static function sanitize(string $svg): string
    {
        // Nessun SVG legittimo ha bisogno di un DOCTYPE con entità: rimosso a monte,
        // prima ancora del parsing XML, per eliminare la superficie XXE.
        $withoutDoctype = preg_replace('/<!DOCTYPE[^>]*>/i', '', $svg) ?? $svg;

        libxml_use_internal_errors(true);
        $document = new DOMDocument;
        $loaded = $document->loadXML($withoutDoctype, LIBXML_NONET);
        libxml_clear_errors();

        if (! $loaded || $document->documentElement === null) {
            return '<svg xmlns="http://www.w3.org/2000/svg"></svg>';
        }

        $xpath = new DOMXPath($document);
        $elements = $xpath->query('//*') ?: [];

        foreach ($elements as $element) {
            if (! $element instanceof DOMElement || $element->parentNode === null) {
                continue;
            }

            if (! in_array($element->tagName, self::ALLOWED_ELEMENTS, true)) {
                $element->parentNode->removeChild($element);

                continue;
            }

            self::sanitizeAttributes($element);
        }

        return $document->saveXML() ?: '';
    }

    private static function sanitizeAttributes(DOMElement $element): void
    {
        $names = [];

        foreach ($element->attributes ?? [] as $attribute) {
            $names[] = $attribute->nodeName;
        }

        foreach ($names as $name) {
            $value = $element->getAttribute($name);
            $lowerName = strtolower($name);

            if (in_array($lowerName, self::URL_ATTRIBUTES, true)) {
                if (! str_starts_with($value, '#')) {
                    $element->removeAttribute($name);
                }

                continue;
            }

            if (! in_array($name, self::ALLOWED_ATTRIBUTES, true) || str_starts_with($lowerName, 'on')) {
                $element->removeAttribute($name);

                continue;
            }

            if (str_contains(strtolower($value), 'javascript:')) {
                $element->removeAttribute($name);
            }
        }
    }
}
