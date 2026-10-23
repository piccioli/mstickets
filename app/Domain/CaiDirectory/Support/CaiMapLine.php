<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

use App\Domain\CaiDirectory\Enums\CaiTrailPracticability;
use App\Domain\CaiDirectory\Models\CaiTrail;
use App\Support\Formatting\ItalianNumberFormatter;

/**
 * Linea del componente `x-cai.map` (US-1033, sentieri dalla US-1053): una geometria GeoJSON (`LineString`/
 * `MultiLineString`, o una `Feature`) disegnata con il colore, lo spessore e il tratteggio (`dash`, facoltativo) indicati. `popupHtml` come per i marker;
 * `id` è la chiave con cui la mappa e l'elenco della pagina si evidenziano a vicenda.
 */
final readonly class CaiMapLine
{
    /** Spessore di un sentiero a riposo, come nel design "Sentieri di Sezione" (evidenziato: 5 / 5,5 nel componente). */
    public const TRAIL_WEIGHT = 3;

    /** Tappe SICAI (US-1095), colori del design "Tappe SICAI del GR": principali a colori alternati, varianti punteggiate. */
    public const SICAI_COLOR = '#17a180';

    public const SICAI_ALTERNATE_COLOR = '#0b5c49';

    public const SICAI_WEIGHT = 4;

    public const SICAI_VARIANT_WEIGHT = 3;

    public const SICAI_VARIANT_DASH = '0.1 7';

    public const SICAI_NOT_PRACTICABLE_DASH = '8 6';

    /**
     * @param  array<string, mixed>  $geojson
     */
    public function __construct(
        public int|string $id,
        public array $geojson,
        public string $color,
        public ?string $label = null,
        public int $weight = 4,
        public ?string $popupHtml = null,
        public ?string $dash = null,
    ) {}

    /**
     * Linea di un sentiero, colorata per stato di accatastamento, con l'anteprima del design; `null` se il sentiero non
     * ha geometria. Dal GR (US-1064) l'anteprima mostra anche il settore (`$place`) e le Sezioni (`$sections`, lista
     * vuota = "Nessuna Sezione").
     *
     * @param  list<string>|null  $sections
     */
    public static function forTrail(CaiTrail $trail, ?string $url = null, ?string $place = null, ?array $sections = null): ?self
    {
        if ($trail->geometry === null || $trail->geometry === []) {
            return null;
        }

        return new self(
            id: $trail->id,
            geojson: $trail->geometry,
            color: $trail->status->color(),
            label: collect([$trail->ref, $trail->name])->filter(fn (?string $part): bool => filled($part))->implode(' · ') ?: null,
            weight: self::TRAIL_WEIGHT,
            popupHtml: view('components.cai.map-trail-popup', [
                'ref' => $trail->ref,
                'name' => $trail->name,
                // Come nel design: "partenza → arrivo" con "—" al posto del dato mancante.
                'route' => (filled($trail->from_place) ? $trail->from_place : '—').' → '.(filled($trail->to_place) ? $trail->to_place : '—'),
                'statusLabel' => $trail->status->getLabel(),
                'statusColor' => $trail->status->color(),
                'practicabilityLabel' => $trail->practicability->getLabel(),
                'practicabilityBackground' => $trail->practicability->background(),
                'practicabilityForeground' => $trail->practicability->foreground(),
                'km' => $trail->length_km === null ? null : ItalianNumberFormatter::kilometers((float) $trail->length_km),
                'place' => $place,
                'sections' => $sections,
                'url' => $url,
            ])->render(),
        );
    }

    /**
     * Linea di una tappa SICAI del GR (US-1095) con l'anteprima del design: non percorribile rossa tratteggiata, con
     * difficoltà ambra, variante verde punteggiata, le principali verdi alternando `$alternate` (una sì e una no);
     * `$geometry` già semplificata, `null` se la tappa non ha tracciato.
     *
     * @param  array<string, mixed>|null  $geometry
     */
    public static function forSicaiStage(CaiRegionalGroupSicaiStageRow $row, ?array $geometry, bool $alternate, ?string $url = null): ?self
    {
        if ($geometry === null || $geometry === []) {
            return null;
        }

        $practicability = $row->practicability();
        [$color, $weight, $dash] = match (true) {
            $practicability === CaiTrailPracticability::NotPracticable => [$practicability->dot(), self::SICAI_WEIGHT, self::SICAI_NOT_PRACTICABLE_DASH],
            $practicability === CaiTrailPracticability::WithDifficulty => [$practicability->dot(), self::SICAI_WEIGHT, null],
            $row->isVariant => [self::SICAI_COLOR, self::SICAI_VARIANT_WEIGHT, self::SICAI_VARIANT_DASH],
            default => [$alternate ? self::SICAI_ALTERNATE_COLOR : self::SICAI_COLOR, self::SICAI_WEIGHT, null],
        };

        return new self(
            id: $row->stage->cai_trail_id,
            geojson: $geometry,
            color: $color,
            label: $row->code(),
            weight: $weight,
            popupHtml: view('components.cai.map-sicai-stage-popup', [
                'code' => $row->code(),
                'variantOf' => $row->mainCode,
                'route' => $row->route(),
                'km' => $row->km(),
                'ascent' => $row->ascent(),
                'practicability' => $practicability,
                'url' => $url,
            ])->render(),
            dash: $dash,
        );
    }

    /**
     * `dash` (tratteggio SVG, es. "8 6") solo se indicato: le mappe di rifugi e sentieri mantengono il payload di prima.
     *
     * @return array{id: int|string, geojson: array<string, mixed>, color: string, label: string|null, weight: int, popupHtml: string|null, dash?: string}
     */
    public function toArray(): array
    {
        $line = [
            'id' => $this->id,
            'geojson' => $this->geojson,
            'color' => $this->color,
            'label' => $this->label,
            'weight' => $this->weight,
            'popupHtml' => $this->popupHtml,
        ];

        if ($this->dash !== null) {
            $line['dash'] = $this->dash;
        }

        return $line;
    }
}
