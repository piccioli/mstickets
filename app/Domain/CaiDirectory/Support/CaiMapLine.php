<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

use App\Domain\CaiDirectory\Models\CaiTrail;
use App\Support\Formatting\ItalianNumberFormatter;

/**
 * Linea del componente `x-cai.map` (US-1033, sentieri dalla US-1053): una geometria GeoJSON (`LineString`/
 * `MultiLineString`, o una `Feature`) disegnata con il colore e lo spessore indicati. `popupHtml` come per i marker;
 * `id` è la chiave con cui la mappa e l'elenco della pagina si evidenziano a vicenda.
 */
final readonly class CaiMapLine
{
    /** Spessore di un sentiero a riposo, come nel design "Sentieri di Sezione" (evidenziato: 5 / 5,5 nel componente). */
    public const TRAIL_WEIGHT = 3;

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
    ) {}

    /**
     * Linea di un sentiero, colorata per stato di accatastamento, con l'anteprima del design; `null` se il sentiero non
     * ha geometria.
     */
    public static function forTrail(CaiTrail $trail, ?string $url = null): ?self
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
                'url' => $url,
            ])->render(),
        );
    }

    /**
     * @return array{id: int|string, geojson: array<string, mixed>, color: string, label: string|null, weight: int, popupHtml: string|null}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'geojson' => $this->geojson,
            'color' => $this->color,
            'label' => $this->label,
            'weight' => $this->weight,
            'popupHtml' => $this->popupHtml,
        ];
    }
}
