<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

/**
 * Linea del componente `x-cai.map` (US-1033, predisposta per i sentieri della 10.6): una geometria GeoJSON
 * (`LineString`/`MultiLineString`, o una `Feature`) disegnata con il colore indicato. `popupHtml` come per i marker.
 */
final readonly class CaiMapLine
{
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
