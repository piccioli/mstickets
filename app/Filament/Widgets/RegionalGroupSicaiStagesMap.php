<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domain\CaiDirectory\Enums\CaiTrailPracticability;
use App\Domain\CaiDirectory\Queries\CaiRegionalGroupSicai;
use App\Domain\CaiDirectory\Support\CaiMapLine;
use App\Domain\CaiDirectory\Support\CaiMapMarker;
use App\Domain\CaiDirectory\Support\CaiSicaiRestPointType;
use App\Domain\Identity\Models\User;
use App\Filament\Pages\RegionalGroupSicaiStages;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

/**
 * Mappa della pagina "Tappe SICAI del GR" (Fase 10.9, US-1095), componente Livewire figlio di
 * {@see RegionalGroupSicaiStages}: i tracciati delle tappe (~240 KB per il Piemonte, semplificati a zoom
 * {@see GEOMETRY_ZOOM}) non vengono rispediti a ogni filtro o ricerca dell'elenco. Tracciato a colori alternati
 * ({@see CaiMapLine::forSicaiStage()}), punti di appoggio come marker per tipo ({@see CaiMapMarker::forSicaiRestPoint()}),
 * anteprima al clic; riquadro informativo in alto e legenda (in basso a sinistra su desktop, sotto la mappa su mobile).
 *
 * Non scoperto dal pannello, non lazy (lo script di `x-cai.map` deve arrivare col primo caricamento). Stesso gate della
 * pagina.
 */
class RegionalGroupSicaiStagesMap extends Widget
{
    /** Zoom per cui si semplificano i tracciati: nitidi fino a ~13, dove ormai si apre la scheda. */
    public const GEOMETRY_ZOOM = 12;

    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.regional-group-sicai-stages-map';

    public static function canView(): bool
    {
        return RegionalGroupSicaiStages::canAccess();
    }

    /**
     * @return array{name: string, lines: list<CaiMapLine>, markers: list<CaiMapMarker>, stagesCount: int, pointsCount: int, hasVariants: bool, pointTypes: list<CaiSicaiRestPointType>, practicabilities: array{difficulty: CaiTrailPracticability, notPracticable: CaiTrailPracticability}}
     */
    protected function getViewData(): array
    {
        $user = Auth::user();
        $region = $user instanceof User ? $user->region : null;
        $data = $region === null ? null : CaiRegionalGroupSicai::forRegion($region);
        $rows = $data->stages ?? [];
        $geometries = CaiRegionalGroupSicai::geometries($rows, self::GEOMETRY_ZOOM);

        $lines = [];
        $mainIndex = 0;

        foreach ($rows as $row) {
            $alternate = ! $row->isVariant && $mainIndex++ % 2 === 1;
            $line = CaiMapLine::forSicaiStage(
                $row,
                $geometries[$row->stage->cai_trail_id] ?? null,
                $alternate,
                RegionalGroupSicaiStages::stageUrl($row->stage->cai_trail_id),
            );

            if ($line !== null) {
                $lines[] = $line;
            }
        }

        $markers = [];

        foreach ($data->restPointsByType ?? [] as $type => $points) {
            foreach ($points as $point) {
                $marker = CaiMapMarker::forSicaiRestPoint($point, $type, RegionalGroupSicaiStages::POINTS_ANCHOR);

                if ($marker !== null) {
                    $markers[] = $marker;
                }
            }
        }

        return [
            'name' => RegionalGroupSicaiStages::MAP_NAME,
            'lines' => $lines,
            'markers' => $markers,
            'stagesCount' => count($rows),
            'pointsCount' => $data->restPointsCount ?? 0,
            'hasVariants' => ($data->variantsCount ?? 0) > 0,
            'pointTypes' => array_map(CaiSicaiRestPointType::of(...), array_keys($data->restPointsByType ?? [])),
            'practicabilities' => [
                'difficulty' => CaiTrailPracticability::WithDifficulty,
                'notPracticable' => CaiTrailPracticability::NotPracticable,
            ],
        ];
    }
}
