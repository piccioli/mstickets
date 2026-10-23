<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiTrailPracticability;
use App\Domain\CaiDirectory\Enums\CaiTrailSort;
use App\Domain\CaiDirectory\Enums\CaiTrailStatus;
use App\Domain\CaiDirectory\Queries\CaiRegionalGroupTrails;
use App\Domain\CaiDirectory\Support\CaiGeoJsonBounds;
use App\Domain\CaiDirectory\Support\CaiMapArea;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupTrailFilters;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupTrailRow;
use App\Domain\Identity\Enums\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/** @return list<string> */
function grTrailRefs(CaiRegionalGroupTrailFilters $filters = new CaiRegionalGroupTrailFilters, int $perPage = 50, int $page = 1): array
{
    return array_map(
        fn (CaiRegionalGroupTrailRow $row): string => (string) $row->trail->ref,
        CaiRegionalGroupTrails::trails(Region::Lombardia, $filters, $perPage, $page)->items(),
    );
}

test('the two totals differ: territory km include trails without a section, managed km count shared trails once', function (): void {
    grTrailsFixture();

    $data = CaiRegionalGroupTrails::forRegion(Region::Lombardia);

    expect($data->regionId)->toBe(1)
        ->and($data->codiceCai)->toBe('9300000')
        ->and($data->groupName)->toBe('GR Lombardia')
        ->and($data->territory->km())->toEqualWithDelta(30.7, 0.001)
        ->and($data->territory->kmOf(CaiTrailStatus::Difficulty))->toEqualWithDelta(3.2, 0.001)
        ->and($data->territory->consolidatedKm())->toEqualWithDelta(22.0, 0.001)
        ->and($data->territory->consolidatedShare())->toEqualWithDelta(22.0 / 30.7, 0.0001)
        // "10" + "2" (una sola volta, anche se di due Sezioni) + "5" (non presente ma della Sezione Bergamo); non "E1" (Torino).
        ->and($data->managedKm)->toEqualWithDelta(36.0, 0.001)
        ->and($data->managedCount)->toBe(3);
});

test('present trails are those with at least 50% in the region, counted without section, shared and by practicability', function (): void {
    grTrailsFixture();

    $data = CaiRegionalGroupTrails::forRegion(Region::Lombardia);

    expect($data->territory->trailsCount())->toBe(4)
        ->and($data->territory->countOf(CaiTrailStatus::Reconnaissance))->toBe(1)
        ->and($data->presentWithoutSectionCount)->toBe(1)
        ->and($data->presentWithoutSectionKm)->toEqualWithDelta(4.0, 0.001)
        // "2" (Milano + Bergamo) e "E1" (Torino + un codice senza Sezione: conta una Sezione sola) → solo "2".
        ->and($data->presentSharedCount)->toBe(1)
        ->and(array_keys($data->practicability))->toBe(array_map(fn (CaiTrailPracticability $p): string => $p->value, CaiTrailPracticability::cases()))
        ->and($data->practicability[CaiTrailPracticability::Practicable->value])->toEqual(['km' => 16.0, 'count' => 2])
        ->and($data->practicability[CaiTrailPracticability::WithDifficulty->value])->toEqual(['km' => 4.0, 'count' => 1])
        ->and($data->practicability[CaiTrailPracticability::NotPracticable->value])->toEqual(['km' => 0.0, 'count' => 0])
        ->and($data->practicability[CaiTrailPracticability::Unknown->value])->toEqual(['km' => 8.0, 'count' => 1]);
});

test('provinces list their km by status and sectors, empty sectors included', function (): void {
    grTrailsFixture();

    $data = CaiRegionalGroupTrails::forRegion(Region::Lombardia);
    [$bergamo, $brescia, $cremona] = $data->provinces;

    expect(array_map(fn ($p) => $p->name, $data->provinces))->toBe(['Bergamo', 'Brescia', 'Cremona'])
        ->and($data->sectorsCount())->toBe(4)
        ->and($bergamo->stats->km())->toEqualWithDelta(22.8, 0.001)
        ->and($bergamo->stats->trailsCount())->toBe(2)
        ->and($bergamo->stats->consolidatedShare())->toEqualWithDelta(22.0 / 22.8, 0.0001)
        ->and(array_map(fn ($s) => $s->code, $bergamo->sectors))->toBe(['BG01', 'BG02'])
        ->and($bergamo->sectors[0]->hasTrails)->toBeTrue()
        ->and($bergamo->sectors[0]->stats->trailsCount())->toBe(2)
        ->and($bergamo->sectors[1]->hasTrails)->toBeFalse()
        ->and($bergamo->sectors[1]->stats->km())->toBe(0.0)
        ->and($bergamo->emptySectorsCount())->toBe(1)
        ->and($brescia->stats->kmOf(CaiTrailStatus::Reconnaissance))->toEqualWithDelta(4.7, 0.001)
        ->and($brescia->stats->consolidatedShare())->toBe(0.0)
        ->and($cremona->stats->km())->toBe(0.0)
        ->and($cremona->stats->consolidatedShare())->toBeNull()
        ->and($cremona->sectorsWithTrailsCount())->toBe(0)
        ->and($cremona->emptySectorsCount())->toBe(1);
});

test('sections of the group include those without trails, and a shared trail counts for each', function (): void {
    grTrailsFixture();

    $data = CaiRegionalGroupTrails::forRegion(Region::Lombardia);
    $sections = collect($data->sections)->keyBy('codiceCai');

    expect(array_map(fn ($s) => $s->codiceCai, $data->sections))->toBe(['9300002', '9300001', '9300003'])
        ->and($sections['9300001']->stats->trailsCount())->toBe(2)
        ->and($sections['9300001']->stats->km())->toEqualWithDelta(16.0, 0.001)
        ->and($sections['9300001']->sharedCount)->toBe(1)
        ->and($sections['9300002']->stats->km())->toEqualWithDelta(26.0, 0.001)
        ->and($sections['9300002']->stats->kmOf(CaiTrailStatus::Validated))->toEqualWithDelta(20.0, 0.001)
        ->and($sections['9300003']->stats->trailsCount())->toBe(0)
        ->and($sections['9300003']->stats->km())->toBe(0.0)
        ->and($sections['9300003']->sharedCount)->toBe(0)
        // La somma delle Sezioni supera i km gestiti: il sentiero "2" conta per due Sezioni.
        ->and($data->sectionsKm())->toEqualWithDelta(42.0, 0.001)
        ->and($data->sectionsKm())->toBeGreaterThan($data->managedKm);
});

test('the list contains only the present trails of the region, in natural number order', function (): void {
    grTrailsFixture();

    $page = CaiRegionalGroupTrails::trails(Region::Lombardia, new CaiRegionalGroupTrailFilters);
    $rows = collect($page->items())->keyBy(fn (CaiRegionalGroupTrailRow $r): string => (string) $r->trail->ref);

    expect(grTrailRefs())->toBe(['2', '2A', '10', 'E1'])
        ->and($page->total())->toBe(4)
        ->and($rows['2']->province?->name)->toBe('Bergamo')
        ->and($rows['2']->sector?->code)->toBe('BG01')
        ->and(array_map(fn ($s) => $s->name, $rows['2']->sections()))->toBe(['SEZ. BERGAMO', 'SEZ. MILANO'])
        ->and($rows['2']->isShared())->toBeTrue()
        ->and($rows['2A']->sections())->toBe([])
        // "E1": settore con la quota maggiore (BS01, 40%) e la sua provincia.
        ->and($rows['E1']->sector?->code)->toBe('BS01')
        ->and($rows['E1']->province?->name)->toBe('Brescia')
        ->and($rows['E1']->trail->getAttributes())->not->toHaveKey('geometry');
});

test('the list filters by province, sector, section, status and practicability', function (array $filters, array $expected): void {
    grTrailsFixture();

    expect(grTrailRefs(new CaiRegionalGroupTrailFilters(...$filters)))->toBe($expected);
})->with([
    'province' => [['provinceId' => 10], ['2', '10']],
    'another province' => [['provinceId' => 11], ['2A', 'E1']],
    'sector, only trails present at 50%' => [['sectorId' => 102], ['2A']],
    'empty sector' => [['sectorId' => 101], []],
    'section' => [['section' => '9300001'], ['2', '10']],
    'section of another group' => [['section' => '9230001'], ['E1']],
    'without section' => [['section' => CaiRegionalGroupTrailFilters::WITHOUT_SECTION], ['2A']],
    'status' => [['status' => CaiTrailStatus::Validated], ['10']],
    'practicability' => [['practicability' => CaiTrailPracticability::Unknown], ['E1']],
    'combined' => [['provinceId' => 10, 'status' => CaiTrailStatus::PreValidated], ['2']],
]);

test('the search matches the start of the number or part of name, start or end', function (string $search, array $expected): void {
    grTrailsFixture();

    expect(grTrailRefs(new CaiRegionalGroupTrailFilters(search: $search)))->toBe($expected);
})->with([
    'number prefix' => ['2', ['2', '2A']],
    'number, case-insensitive' => ['e1', ['E1']],
    'not inside the number' => ['1', ['10']],
    'name' => ['orobie', ['10']],
    'start place' => ['gavia', ['2A']],
    'no match' => ['zzz', []],
]);

test('the list is sorted by km or status, ties and missing numbers by number', function (): void {
    grTrailsFixture();

    expect(grTrailRefs(new CaiRegionalGroupTrailFilters(sort: CaiTrailSort::KmDesc)))->toBe(['10', 'E1', '2', '2A'])
        ->and(grTrailRefs(new CaiRegionalGroupTrailFilters(sort: CaiTrailSort::KmAsc)))->toBe(['2A', '2', 'E1', '10'])
        ->and(grTrailRefs(new CaiRegionalGroupTrailFilters(sort: CaiTrailSort::StatusAsc)))->toBe(['2A', 'E1', '2', '10'])
        ->and(grTrailRefs(new CaiRegionalGroupTrailFilters(sort: CaiTrailSort::NumberDesc)))->toBe(['E1', '10', '2A', '2']);
});

test('the list is paginated in SQL with a constant number of queries', function (): void {
    grTrailsFixture();

    $queries = function (int $perPage, int $page): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $paginator = CaiRegionalGroupTrails::trails(Region::Lombardia, new CaiRegionalGroupTrailFilters, $perPage, $page);
        $paginator->items();
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    };

    $page = CaiRegionalGroupTrails::trails(Region::Lombardia, new CaiRegionalGroupTrailFilters, 3, 2);

    expect(array_map(fn (CaiRegionalGroupTrailRow $r): string => (string) $r->trail->ref, $page->items()))->toBe(['E1'])
        ->and($page->total())->toBe(4)
        ->and($page->lastPage())->toBe(2)
        ->and($queries(1, 1))->toBe($queries(4, 1));
});

test('the page data uses a constant number of queries', function (): void {
    grTrailsFixture();

    DB::enableQueryLog();
    CaiRegionalGroupTrails::forRegion(Region::Lombardia);
    $few = count(DB::getQueryLog());

    caiSection(['codice_cai' => '9300004', 'name' => 'SEZ. COMO', 'region' => 'LOMBARDIA']);
    caiTrail(['ref' => '77'], ['9300004']);
    DB::table('cai_trail_provinces')->insert(['id' => 13, 'region_id' => 1, 'name' => 'Como', 'code' => 'CO']);

    DB::flushQueryLog();
    CaiRegionalGroupTrails::forRegion(Region::Lombardia);

    expect(count(DB::getQueryLog()))->toBe($few);
});

test('a region without osm2cai data has zero totals and an empty list, a group without sections no code', function (): void {
    grTrailsFixture();

    $sicilia = CaiRegionalGroupTrails::forRegion(Region::Sicilia);

    expect($sicilia->regionId)->toBeNull()
        ->and($sicilia->codiceCai)->toBeNull()
        ->and($sicilia->territory->km())->toBe(0.0)
        ->and($sicilia->territory->trailsCount())->toBe(0)
        ->and($sicilia->managedKm)->toBe(0.0)
        ->and($sicilia->provinces)->toBe([])
        ->and($sicilia->sections)->toBe([])
        ->and($sicilia->practicability[CaiTrailPracticability::Practicable->value])->toEqual(['km' => 0.0, 'count' => 0])
        ->and(CaiRegionalGroupTrails::trails(Region::Sicilia, new CaiRegionalGroupTrailFilters)->total())->toBe(0);

    $molise = CaiRegionalGroupTrails::forRegion(Region::Molise);

    expect($molise->territory->km())->toEqualWithDelta(7.0, 0.001)
        ->and($molise->managedKm)->toEqualWithDelta(7.0, 0.001)
        ->and(array_map(fn ($p) => $p->name, $molise->provinces))->toBe(['Campobasso']);
});

/** Sposta il riquadro di un sentiero della fixture (la geometria resta quella di default). */
function moveGrTrail(string $ref, float $minLat, float $minLng, float $maxLat, float $maxLng): void
{
    DB::table('cai_trails')->where('ref', $ref)->update(['min_lat' => $minLat, 'min_lng' => $minLng, 'max_lat' => $maxLat, 'max_lng' => $maxLng]);
}

test('the map trails in bounds are the present trails whose box intersects it, longest first, up to the limit', function (): void {
    grTrailsFixture();
    // "2A" lontano dal riquadro, "E1" lo tocca solo sul bordo; "5" e "" sono nel riquadro ma non presenti.
    moveGrTrail('2A', 46.0, 9.0, 46.1, 9.1);
    moveGrTrail('E1', 43.3, 10.25, 43.5, 10.4);
    $bounds = new CaiGeoJsonBounds(minLat: 43.0, minLng: 10.0, maxLat: 43.3, maxLng: 10.3);
    $regionId = (int) CaiRegionalGroupTrails::regionId(Region::Lombardia);

    $refs = fn (array $result): array => array_map(fn (CaiRegionalGroupTrailRow $row): string => (string) $row->trail->ref, $result['rows']);
    $all = CaiRegionalGroupTrails::inBounds($regionId, $bounds, 10);
    $limited = CaiRegionalGroupTrails::inBounds($regionId, $bounds, 2);

    expect($refs($all))->toBe(['10', 'E1', '2'])
        ->and($all['total'])->toBe(3)
        ->and($refs($limited))->toBe(['10', 'E1'])
        ->and($limited['total'])->toBe(3)
        // Con geometria, Sezioni e il settore della quota maggiore, come le righe dell'elenco.
        ->and($all['rows'][0]->trail->geometry)->toBeArray()
        ->and($all['rows'][0]->sector?->full_code)->toBe('LBG01')
        ->and(array_map(fn ($section): string => $section->name, $all['rows'][2]->sections()))->toBe(['SEZ. BERGAMO', 'SEZ. MILANO'])
        ->and($refs(CaiRegionalGroupTrails::inBounds($regionId, new CaiGeoJsonBounds(45.9, 8.9, 46.2, 9.2), 10)))->toBe(['2A']);
});

test('a trail without bounds never reaches the map and the query count does not depend on the trails', function (): void {
    grTrailsFixture();
    DB::table('cai_trails')->where('ref', '10')->update(['geometry' => null, 'min_lat' => null, 'min_lng' => null, 'max_lat' => null, 'max_lng' => null]);
    $regionId = (int) CaiRegionalGroupTrails::regionId(Region::Lombardia);

    DB::flushQueryLog();
    DB::enableQueryLog();
    $result = CaiRegionalGroupTrails::inBounds($regionId, new CaiGeoJsonBounds(40.0, 5.0, 48.0, 15.0), 10);
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($result['total'])->toBe(3)
        ->and(array_map(fn (CaiRegionalGroupTrailRow $row): string => (string) $row->trail->ref, $result['rows']))->not->toContain('10')
        // conteggio, sentieri, Sezioni, territori con province e settori.
        ->and($queries)->toBeLessThanOrEqual(6);
});

test('the sector areas of the map carry geometry, consolidated colour and the empty state', function (): void {
    grTrailsFixture();
    $square = fn (float $lng, float $lat): array => ['type' => 'MultiPolygon', 'coordinates' => [[[[$lng, $lat], [$lng + 0.1, $lat], [$lng + 0.1, $lat + 0.1], [$lng, $lat]]]]];
    DB::table('cai_trail_sectors')->where('id', 100)->update(['geometry' => json_encode($square(9.6, 45.9))]);
    DB::table('cai_trail_sectors')->where('id', 101)->update(['geometry' => json_encode($square(9.7, 45.6))]);
    DB::table('cai_trail_sectors')->where('id', 102)->update(['geometry' => json_encode($square(10.3, 46.0))]);

    $areas = CaiRegionalGroupTrails::sectorAreas(CaiRegionalGroupTrails::forRegion(Region::Lombardia));
    $byId = collect($areas)->keyBy('id');

    // CR01 senza geometria non c'è; ordine delle province (Bergamo, Brescia) e dei settori.
    expect(array_map(fn (CaiMapArea $area): string => $area->id, $areas))->toBe(['settore-100', 'settore-101', 'settore-102'])
        // BG01: 22,0 km consolidati su 22,8 (96%) → ultima fascia; BS01: 0 su 6,4 → prima.
        ->and($byId['settore-100']->color)->toBe('#1f4fa8')
        ->and($byId['settore-100']->empty)->toBeFalse()
        ->and($byId['settore-100']->label)->toBe('LBG01')
        ->and($byId['settore-100']->geojson['type'])->toBe('MultiPolygon')
        ->and($byId['settore-100']->popupHtml)->toContain('Settore LBG01 · BERGAMO')->toContain('Val Brembana')
        ->toContain('2 sentieri · 22,8 km')->toContain('<strong>96%</strong>')
        ->and($byId['settore-101']->empty)->toBeTrue()
        ->and($byId['settore-101']->popupHtml)->toContain('Nessun sentiero censito in questo settore')->not->toContain('Zooma sul settore')
        ->and($byId['settore-102']->color)->toBe('#e3ecfa');
});
