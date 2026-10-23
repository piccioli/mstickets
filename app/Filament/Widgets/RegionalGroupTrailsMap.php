<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Domain\CaiDirectory\Queries\CaiRegionalGroupTrails;
use App\Domain\CaiDirectory\Support\CaiMapArea;
use App\Domain\Identity\Models\User;
use App\Filament\Pages\RegionalGroupTrails;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

/**
 * Mappa per settori della pagina "Sentieri del GR" (Fase 10.7, US-1065), componente Livewire figlio di
 * {@see RegionalGroupTrails}: i settori della regione pesano 300–600 KB di GeoJSON, e in un componente a sé non vengono
 * rispediti a ogni filtro, ricerca o cambio di pagina dell'elenco (Livewire non ridisegna i figli col padre). Le linee
 * arrivano per area visibile dall'endpoint della US-1064.
 *
 * Non scoperto dal pannello (niente Dashboard), non lazy (lo script di `x-cai.map` deve arrivare col primo
 * caricamento). Stesso gate della pagina.
 */
class RegionalGroupTrailsMap extends Widget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected string $view = 'filament.widgets.regional-group-trails-map';

    public static function canView(): bool
    {
        return RegionalGroupTrails::canAccess();
    }

    /** @return array{name: string, areas: list<CaiMapArea>, linesUrl: string|null} */
    protected function getViewData(): array
    {
        $user = Auth::user();
        $region = $user instanceof User ? $user->region : null;
        $data = $region === null ? null : CaiRegionalGroupTrails::forRegion($region);

        return [
            'name' => RegionalGroupTrails::MAP_NAME,
            'areas' => $data === null ? [] : CaiRegionalGroupTrails::sectorAreas($data),
            'linesUrl' => $data?->regionId === null ? null : route('cai-trail-regions.map-trails', ['caiTrailRegion' => $data->regionId]),
        ];
    }
}
