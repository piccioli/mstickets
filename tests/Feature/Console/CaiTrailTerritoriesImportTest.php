<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiTrailStatus;
use App\Domain\CaiDirectory\Enums\CaiTrailTerritoryType;
use App\Domain\CaiDirectory\Import\CaiDatapackImporter;
use App\Domain\CaiDirectory\Import\CaiOsm2caiExtractWriter;
use App\Domain\CaiDirectory\Models\CaiTrail;
use App\Domain\CaiDirectory\Models\CaiTrailProvince;
use App\Domain\CaiDirectory\Models\CaiTrailRegion;
use App\Domain\CaiDirectory\Models\CaiTrailSector;
use App\Domain\CaiDirectory\Models\CaiTrailTerritory;
use App\Domain\CaiDirectory\Models\CaiTrailTerritoryKm;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** Righe di `cai_trail_territory` come `trail|regione|provincia|settore|percentuale`, ordinate. */
function trailTerritoryRows(): array
{
    return CaiTrailTerritory::query()->get()
        ->map(fn (CaiTrailTerritory $row): string => implode('|', [$row->cai_trail_id, $row->region_id, $row->province_id ?? '-', $row->sector_id ?? '-', $row->percentage]))
        ->sort()->values()->all();
}

test('imports regions, provinces, sectors, km per territory and trail links from the datapack fixture', function (): void {
    $results = app(CaiDatapackImporter::class)->import(makeTrailTerritoriesDatapack(), false);

    // La fixture tiene 3 regioni italiane (Ticino scartato dal writer), 2 province, 2 settori, 4 righe di km, 4 legami.
    expect($results['snapshot_territori_sentieri']->read)->toBe(15)
        ->and($results['snapshot_territori_sentieri']->created)->toBe(15)
        ->and($results['snapshot_territori_sentieri']->deleted)->toBe(0)
        ->and($results['snapshot_territori_sentieri']->warnings)->toBe([]);

    $lombardia = CaiTrailRegion::query()->findOrFail(1);
    expect($lombardia->name)->toBe('Lombardia')
        ->and($lombardia->region_key)->toBe(Region::Lombardia)
        ->and(CaiTrailRegion::query()->orderBy('id')->pluck('region_key')->all())->toBe([Region::Lombardia, Region::TrentinoAltoAdige, Region::ValleDAosta])
        ->and($lombardia->provinces()->pluck('code')->all())->toBe(['SO']);

    $sector = CaiTrailSector::query()->findOrFail(100);
    expect($sector->name)->toBe('Valtellina')
        ->and($sector->code)->toBe('A')
        ->and($sector->full_code)->toBe('DSOA1')
        ->and($sector->geometry)->toBe(['type' => 'MultiPolygon', 'coordinates' => [[[[10.0, 46.0], [10.5, 46.0], [10.5, 46.5], [10.0, 46.0]]]]])
        ->and($sector->province?->name)->toBe('Sondrio')
        ->and(CaiTrailProvince::query()->findOrFail(10)->sectors()->count())->toBe(2);

    $km = CaiTrailTerritoryKm::query()->where('territory_type', CaiTrailTerritoryType::Region)->where('territory_id', 1)->where('status', CaiTrailStatus::Validated)->firstOrFail();
    expect($km->km)->toBe('5.432')
        ->and($km->present_routes)->toBe(1)
        ->and(CaiTrailTerritoryKm::query()->count())->toBe(4);

    expect(trailTerritoryRows())->toBe(['101|1|-|-|100.00', '101|1|10|-|100.00', '101|1|10|100|100.00', '102|1|-|-|50.00'])
        ->and(CaiTrail::query()->findOrFail(101)->territories()->count())->toBe(3)
        ->and(CaiTrail::query()->findOrFail(102)->territories()->first()?->region?->name)->toBe('Lombardia');
});

test('a second run writes nothing', function (): void {
    $sqlite = makeTrailTerritoriesDatapack();
    app(CaiDatapackImporter::class)->import($sqlite, false);
    $ids = CaiTrailTerritory::query()->orderBy('id')->pluck('id')->all();

    $results = app(CaiDatapackImporter::class)->import($sqlite, false);

    expect($results['snapshot_territori_sentieri']->created)->toBe(0)
        ->and($results['snapshot_territori_sentieri']->deleted)->toBe(0)
        ->and($results['snapshot_territori_sentieri']->skipped)->toBe(15)
        // Nessuna riscrittura: gli id autoincrementali dei legami restano gli stessi.
        ->and(CaiTrailTerritory::query()->orderBy('id')->pluck('id')->all())->toBe($ids);
});

test('replaces the content when the source changes', function (): void {
    $sqlite = makeTrailTerritoriesDatapack();
    app(CaiDatapackImporter::class)->import($sqlite, false);

    makeTrailTerritoriesDatapack([
        'sectors.csv' => "id,province_id,code,full_code,name,geometry_geojson\n100,10,A,DSOA1,Valtellina alta,\n",
        'territory_km.csv' => "territory_type,territory_id,status,km,present_routes\nregion,1,4,6.1,1\n",
        'trail_territory.csv' => "trail_id,region_id,province_id,sector_id,percentage\n101,1,,,100\n102,1,,,62.5\n",
    ], $sqlite);
    $results = app(CaiDatapackImporter::class)->import($sqlite, false);

    // Aggiunte: settore rinominato, km cambiati, legame 102 al 62,5%. Sparite: 2 settori, 4 km, 3 legami.
    expect($results['snapshot_territori_sentieri']->created)->toBe(3)
        ->and($results['snapshot_territori_sentieri']->deleted)->toBe(9)
        ->and(CaiTrailSector::query()->pluck('name')->all())->toBe(['Valtellina alta'])
        ->and(CaiTrailSector::query()->findOrFail(100)->geometry)->toBeNull()
        ->and(CaiTrailTerritoryKm::query()->sole()->km)->toBe('6.100')
        ->and(trailTerritoryRows())->toBe(['101|1|-|-|100.00', '102|1|-|-|62.50'])
        ->and(CaiTrailRegion::query()->count())->toBe(3);
});

test('skips orphan rows with a warning', function (): void {
    $sqlite = makeTrailTerritoriesDatapack([
        'trail_territory.csv' => "trail_id,region_id,province_id,sector_id,percentage\n101,1,,,100\n999,1,,,100\n101,1,77,,100\n",
        'territory_km.csv' => "territory_type,territory_id,status,km,present_routes\nregion,1,4,5.432,1\nsector,555,4,1,1\nregion,1,7,1,1\n",
    ]);

    $result = app(CaiDatapackImporter::class)->import($sqlite, false)['snapshot_territori_sentieri'];

    // Territori sconosciuti (settore 555, provincia 77) li scarta già il writer: qui restano stato 7 e sentiero 999.
    expect($result->warnings)->toBe([
        '1 statistiche con territorio sconosciuto o stato fuori da 1–4 saltati',
        '1 legami sentiero-territorio senza sentiero importato o con territorio sconosciuto saltati',
    ])
        ->and(CaiTrailTerritoryKm::query()->count())->toBe(1)
        ->and(trailTerritoryRows())->toBe(['101|1|-|-|100.00']);
});

test('dry-run counts without writing', function (): void {
    $results = app(CaiDatapackImporter::class)->import(makeTrailTerritoriesDatapack(), true);

    expect($results['snapshot_territori_sentieri']->created)->toBe(15)
        ->and($results['snapshot_territori_sentieri']->warnings)->toBe([])
        ->and(CaiTrailRegion::query()->count())->toBe(0)
        ->and(CaiTrailTerritory::query()->count())->toBe(0);
});

test('without the territory tables the step is skipped with a warning and the content is kept', function (): void {
    $sqlite = makeTrailTerritoriesDatapack();
    app(CaiDatapackImporter::class)->import($sqlite, false);
    (new PDO('sqlite:'.$sqlite))->exec('DROP TABLE '.CaiOsm2caiExtractWriter::SECTORS_TABLE);

    $result = app(CaiDatapackImporter::class)->import($sqlite, false)['snapshot_territori_sentieri'];

    expect($result->warnings)->toBe(['Tabelle snap_osm2cai_sectors assenti nel datapack: territori dei sentieri non aggiornati (bin/update-osm2cai-datapack)'])
        ->and($result->read)->toBe(0)
        ->and(CaiTrailSector::query()->count())->toBe(2)
        ->and(CaiTrailTerritory::query()->count())->toBe(4);
});

test('territories are read-only: staff and the regional group of the region see them, nobody writes', function (): void {
    app(CaiDatapackImporter::class)->import(makeTrailTerritoriesDatapack(), false);
    $staff = userWithPermissions(Permission::CaiDirectoryView);
    $lombardia = User::factory()->create(['customer_type' => CustomerType::GruppoRegionale, 'region' => Region::Lombardia]);
    $toscana = User::factory()->create(['customer_type' => CustomerType::GruppoRegionale, 'region' => Region::Toscana]);
    $section = User::factory()->create(['customer_type' => CustomerType::Sezione]);

    $records = [
        CaiTrailRegion::query()->findOrFail(1),
        CaiTrailProvince::query()->findOrFail(10),
        CaiTrailSector::query()->findOrFail(100),
        CaiTrailTerritoryKm::query()->where('territory_type', CaiTrailTerritoryType::Sector)->firstOrFail(),
        CaiTrailTerritoryKm::query()->where('territory_type', CaiTrailTerritoryType::Province)->firstOrFail(),
        CaiTrailTerritory::query()->firstOrFail(),
    ];

    foreach ($records as $record) {
        expect($staff->can('view', $record))->toBeTrue()
            ->and($lombardia->can('view', $record))->toBeTrue()
            ->and($toscana->can('view', $record))->toBeFalse()
            ->and($section->can('view', $record))->toBeFalse()
            ->and($staff->can('update', $record))->toBeFalse()
            ->and($staff->can('delete', $record))->toBeFalse();
    }

    expect($staff->can('viewAny', CaiTrailRegion::class))->toBeTrue()
        ->and($lombardia->can('viewAny', CaiTrailRegion::class))->toBeFalse()
        ->and($staff->can('create', CaiTrailSector::class))->toBeFalse()
        ->and($lombardia->can('view', CaiTrailRegion::query()->findOrFail(2)))->toBeFalse();
});
