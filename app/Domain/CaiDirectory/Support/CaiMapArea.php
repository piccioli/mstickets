<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

use App\Domain\CaiDirectory\Enums\CaiTrailStatus;
use App\Support\Formatting\ItalianNumberFormatter;

/**
 * Area (poligono) del componente `x-cai.map` (US-1064): una geometria GeoJSON (`Polygon`/`MultiPolygon`) riempita col
 * colore indicato; `empty` = area senza dati (rosa a bordo rosso tratteggiato, come "settore senza sentieri" nel design
 * "Sentieri del GR"). `label` è l'etichetta permanente al centro (da zoom 9), `popupHtml` il riquadro al clic. Gli `id`
 * condividono lo spazio di marker e linee: per i settori sono `settore-{id}`.
 */
final readonly class CaiMapArea
{
    /** Scala del design per la quota di km consolidati (stati 3–4) di un settore: cinque fasce da 20 punti. */
    public const CONSOLIDATED_RAMP = [
        ['color' => '#e3ecfa', 'label' => '0–20%'],
        ['color' => '#b3cbf0', 'label' => '20–40%'],
        ['color' => '#7fa6e2', 'label' => '40–60%'],
        ['color' => '#4a81d3', 'label' => '60–80%'],
        ['color' => '#1f4fa8', 'label' => '80–100%'],
    ];

    /** Area senza dati ("settore senza sentieri"): riempimento rosa, bordo rosso tratteggiato. */
    public const EMPTY_FILL = '#fbe7e5';

    public const EMPTY_STROKE = '#b42318';

    /**
     * @param  array<string, mixed>  $geojson
     */
    public function __construct(
        public string $id,
        public array $geojson,
        public string $color,
        public bool $empty = false,
        public ?string $label = null,
        public ?string $popupHtml = null,
    ) {}

    public static function sectorId(int $sectorId): string
    {
        return 'settore-'.$sectorId;
    }

    /** Colore della fascia di {@see CONSOLIDATED_RAMP} per una quota 0–1 (`null` = nessun km → prima fascia). */
    public static function consolidatedColor(?float $share): string
    {
        $band = (int) min(4, max(0, floor(($share ?? 0.0) * 5)));

        return self::CONSOLIDATED_RAMP[$band]['color'];
    }

    /**
     * Settore della mappa del GR, colorato per km consolidati, con il riquadro del design (codice, provincia, nome, sentieri,
     * km, barra per stato, percentuale consolidati; "Nessun sentiero censito" se vuoto). `null` senza geometria.
     *
     * @param  array<string, mixed>|null  $geometry
     */
    public static function forSector(CaiRegionalGroupTrailSectorRow $sector, ?array $geometry, string $provinceName, bool $listable = true): ?self
    {
        if ($geometry === null || $geometry === []) {
            return null;
        }

        $stats = $sector->stats;
        $km = $stats->km();
        $share = $stats->consolidatedShare();
        $code = $sector->fullCode ?? $sector->code;
        $count = $stats->trailsCount();

        return new self(
            id: self::sectorId($sector->id),
            geojson: $geometry,
            color: self::consolidatedColor($share),
            empty: ! $sector->hasTrails,
            label: $code,
            popupHtml: view('components.cai.map-sector-popup', [
                'code' => $code,
                'province' => mb_strtoupper($provinceName),
                'name' => $sector->name ?? $code,
                'empty' => ! $sector->hasTrails,
                'trails' => ItalianNumberFormatter::integer($count).($count === 1 ? ' sentiero' : ' sentieri'),
                'km' => ItalianNumberFormatter::kilometers($km),
                'bar' => collect(CaiTrailStatus::cases())->map(fn (CaiTrailStatus $status): array => [
                    'width' => $km > 0 ? round($stats->kmOf($status) / $km * 100, 2) : 0,
                    'color' => $status->color(),
                    'label' => $status->getLabel(),
                ])->all(),
                'consolidated' => ItalianNumberFormatter::integer(round(($share ?? 0.0) * 100)).'%',
                'listable' => $listable,
            ])->render(),
        );
    }

    /**
     * @return array{id: string, geojson: array<string, mixed>, color: string, empty: bool, label: string|null, popupHtml: string|null}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'geojson' => $this->geojson,
            'color' => $this->color,
            'empty' => $this->empty,
            'label' => $this->label,
            'popupHtml' => $this->popupHtml,
        ];
    }
}
