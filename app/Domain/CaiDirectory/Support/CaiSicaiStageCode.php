<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

/**
 * Codice di una tappa del Sentiero Italia CAI (US-1091), ricavato dal dump osm2cai. Il codice sta nel tag OSM `name`
 * del sentiero ("Sentiero Italia - Tappa F05", varianti "D18S", "SI L01AP") o, se il nome non lo contiene, nel tag
 * `description` (stessa espressione); se manca in entrambi (tratti "AVxx" dell'Alta Via dei Monti Liguri) la lettera si
 * prende dal nome dell'itinerario regionale ("Sentiero Italia - Liguria G00" → G), numero e variante restano nulli e il
 * codice è il `ref` del sentiero ("AV16") o "—".
 *
 * `sortKey` ordina per lettera, numero, variante, con le tappe senza numero in fondo alla loro lettera (e le tappe senza
 * lettera in fondo a tutto): "D018" < "D018S" < "D019" < "D999 …" < "~999 …".
 */
final readonly class CaiSicaiStageCode
{
    public const NAME_PATTERN = '/Tappa\s+(?:SI\s+)?([A-Z])(\d{2})([A-Z]*)/';

    public const ITINERARY_PATTERN = '/([A-Z])00$/';

    public const MISSING_CODE = '—';

    public function __construct(
        public ?string $letter,
        public ?int $number,
        public ?string $variant,
        public string $code,
        public string $sortKey,
    ) {}

    public static function parse(?string $osmName, ?string $osmDescription, ?string $itineraryName, ?string $ref): self
    {
        $match = [];

        if (($osmName !== null && preg_match(self::NAME_PATTERN, $osmName, $match) === 1)
            || ($osmDescription !== null && preg_match(self::NAME_PATTERN, $osmDescription, $match) === 1)) {
            $variant = $match[3] === '' ? null : $match[3];
            $number = (int) $match[2];

            return new self(
                letter: $match[1],
                number: $number,
                variant: $variant,
                code: $match[1].$match[2].($variant ?? ''),
                sortKey: sprintf('%s%03d%s', $match[1], $number, $variant ?? ''),
            );
        }

        $letter = $itineraryName !== null && preg_match(self::ITINERARY_PATTERN, trim($itineraryName), $match) === 1 ? $match[1] : null;
        $ref = $ref === null ? '' : trim($ref);
        $code = $ref === '' ? self::MISSING_CODE : $ref;

        return new self(
            letter: $letter,
            number: null,
            variant: null,
            code: $code,
            sortKey: ($letter ?? '~').'999 '.$code,
        );
    }
}
