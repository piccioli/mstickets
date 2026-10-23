<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

use App\Domain\CaiDirectory\Models\CaiHut;
use App\Support\Formatting\ItalianNumberFormatter;

/**
 * Marker del componente `x-cai.map` (US-1033): cerchio del colore del tipo con la lettera (o l'icona `icon`, US-1095), `popupHtml` facoltativo già
 * reso (e già escapato) lato server, di solito con la vista `components.cai.map-popup`. `id` è la chiave con cui la
 * mappa e l'elenco della pagina si evidenziano a vicenda.
 */
final readonly class CaiMapMarker
{
    /** Colore e lettera di un rifugio con tipo sconosciuto alla fonte (enum `null`). */
    public const UNKNOWN_COLOR = '#5b6475';

    public const UNKNOWN_LETTER = '?';

    public function __construct(
        public int|string $id,
        public float $lat,
        public float $lng,
        public string $label,
        public string $color,
        public string $letter,
        public ?string $popupHtml = null,
        public ?string $icon = null,
    ) {}

    /**
     * Marker di un rifugio, con il popup del design; `null` se il rifugio non ha coordinate. `$section` (nome della
     * Sezione proprietaria o "GR {regione}") compare sotto il nome nell'elenco del GR.
     */
    public static function forHut(CaiHut $hut, ?string $url = null, ?string $section = null): ?self
    {
        if ($hut->latitude === null || $hut->longitude === null) {
            return null;
        }

        $photos = $hut->photos ?? [];
        $photo = is_string($photos[0] ?? null) ? $photos[0] : null;

        return new self(
            id: $hut->id_cai,
            lat: (float) $hut->latitude,
            lng: (float) $hut->longitude,
            label: $hut->name,
            color: $hut->type?->color() ?? self::UNKNOWN_COLOR,
            letter: $hut->type?->letter() ?? self::UNKNOWN_LETTER,
            popupHtml: view('components.cai.map-popup', [
                'title' => $hut->name,
                'section' => $section,
                'photo' => $photo,
                'color' => $hut->type?->color() ?? self::UNKNOWN_COLOR,
                'typeLabel' => $hut->type?->getLabel() ?? 'Tipo non indicato',
                'meta' => $hut->altitude === null ? null : ItalianNumberFormatter::integer($hut->altitude).' m',
                'statusLabel' => $hut->status?->getLabel(),
                'statusBackground' => $hut->status?->background(),
                'statusForeground' => $hut->status?->foreground(),
                'url' => $url,
            ])->render(),
        );
    }

    /**
     * Marker di un punto di appoggio SICAI del GR (US-1095): colore e lettera del tipo ({@see CaiSicaiRestPointType}),
     * anteprima con tipo, nome, comune, tappe vicine e i link; `null` senza coordinate. `$listAnchor` = id dell'elenco
     * dei punti nella pagina ("Nell'elenco dei punti →").
     */
    public static function forSicaiRestPoint(CaiRegionalGroupSicaiRestPointRow $row, string $type, ?string $listAnchor = null): ?self
    {
        $point = $row->point;

        if ($point->latitude === null || $point->longitude === null) {
            return null;
        }

        $style = CaiSicaiRestPointType::of($type);

        return new self(
            id: 'punto-'.$point->id,
            lat: (float) $point->latitude,
            lng: (float) $point->longitude,
            label: $style->label().' · '.$point->name,
            color: $style->color(),
            letter: $style->letter(),
            icon: $style->icon(),
            popupHtml: view('components.cai.map-sicai-point-popup', [
                'type' => $style->label(),
                'color' => $style->color(),
                'name' => $point->name,
                'municipality' => $point->municipality,
                'stageCodes' => $row->stageCodes,
                'hutUrl' => $point->hutUrl(),
                'listAnchor' => $listAnchor,
            ])->render(),
        );
    }

    /**
     * `icon` (path SVG su viewBox 24, al posto della lettera) solo se indicato: le mappe dei rifugi mantengono il payload.
     *
     * @return array{id: int|string, lat: float, lng: float, label: string, color: string, letter: string, popupHtml: string|null, icon?: string}
     */
    public function toArray(): array
    {
        $marker = [
            'id' => $this->id,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'label' => $this->label,
            'color' => $this->color,
            'letter' => $this->letter,
            'popupHtml' => $this->popupHtml,
        ];

        if ($this->icon !== null) {
            $marker['icon'] = $this->icon;
        }

        return $marker;
    }
}
