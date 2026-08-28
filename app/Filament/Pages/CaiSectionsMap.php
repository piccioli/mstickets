<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\Identity\Enums\Permission;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Mappa di tutte le sezioni CAI geolocalizzate (US-805): stesso permesso di
 * `CaiSectionResource` (`Permission::CaiDirectoryView`), nello stesso gruppo di navigazione
 * "Anagrafica CAI" così da comparire accanto all'elenco. Leaflet incluso via CDN nella view
 * (`filament.pages.cai-sections-map`): nessuna dipendenza Leaflet preesistente nel pannello
 * (verificato, US-805), introdotta qui in modo isolato (solo in questa pagina, nessun asset
 * globale del pannello).
 */
class CaiSectionsMap extends Page
{
    protected string $view = 'filament.pages.cai-sections-map';

    protected static ?string $title = 'Mappa sezioni CAI';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?string $navigationLabel = 'Mappa sezioni';

    protected static UnitEnum|string|null $navigationGroup = 'Anagrafica CAI';

    public static function canAccess(): bool
    {
        return (bool) Auth::user()?->can(Permission::CaiDirectoryView);
    }

    /**
     * Solo le sezioni con coordinate note (nullable, US-801): una sezione senza
     * lat/lng non compare come marker, ma resta comunque nell'elenco/export (US-805).
     *
     * @return list<array{codice_cai: string, name: string, region: string, latitude: float, longitude: float}>
     */
    public function sections(): array
    {
        return CaiSection::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get(['codice_cai', 'name', 'region', 'latitude', 'longitude'])
            ->map(fn (CaiSection $section): array => [
                'codice_cai' => $section->codice_cai,
                'name' => $section->name,
                'region' => $section->region,
                'latitude' => (float) $section->latitude,
                'longitude' => (float) $section->longitude,
            ])
            ->values()
            ->all();
    }
}
