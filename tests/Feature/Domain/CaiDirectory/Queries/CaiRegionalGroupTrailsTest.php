<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiTrailPracticability;
use App\Domain\CaiDirectory\Enums\CaiTrailSort;
use App\Domain\CaiDirectory\Enums\CaiTrailStatus;
use App\Domain\CaiDirectory\Queries\CaiRegionalGroupTrails;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupTrailFilters;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupTrailRow;
use App\Domain\Identity\Enums\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Lombardia (regione osm2cai 1) con le province Bergamo (settori BG01 con sentieri, BG02 vuoto), Brescia (BS01) e
 * Cremona (CR01 vuoto, nessun km); Molise (regione 2) con un sentiero solo suo. Sezioni del GR 9300: Milano, Bergamo,
 * Vuota (senza sentieri); Torino è di un altro GR.
 *
 * Sentieri: "10" (Milano, 10 km, validato, presente 100%), "2" (Milano + Bergamo, 6 km, pre-validato, presente),
 * "2A" (nessuna Sezione, 4 km, difficoltà, con difficoltà, presente 80%), "E1" (solo Torino, 8 km, ricognizione,
 * presente esattamente al 50%), "5" (Bergamo, 20 km, validato, solo il 30% in regione: non presente ma gestito),
 * "" (nessuna Sezione, 49,99%: non presente), "M1" (Molise).
 */
function grTrailsFixture(): void
{
    caiSection(['codice_cai' => '9300001', 'name' => 'SEZ. MILANO', 'region' => 'LOMBARDIA']);
    caiSection(['codice_cai' => '9300002', 'name' => 'SEZ. BERGAMO', 'region' => 'LOMBARDIA']);
    caiSection(['codice_cai' => '9300003', 'name' => 'SEZ. VUOTA', 'region' => 'LOMBARDIA']);
    caiSection(['codice_cai' => '9230001', 'name' => 'SEZ. TORINO', 'region' => 'PIEMONTE']);
    caiSection(['codice_cai' => '9720001', 'name' => 'SEZ. CAMPOBASSO', 'region' => 'MOLISE']);

    DB::table('cai_trail_regions')->insert([
        ['id' => 1, 'name' => 'Lombardia', 'region_key' => 'lombardia'],
        ['id' => 2, 'name' => 'Molise', 'region_key' => 'molise'],
    ]);
    DB::table('cai_trail_provinces')->insert([
        ['id' => 10, 'region_id' => 1, 'name' => 'Bergamo', 'code' => 'BG'],
        ['id' => 11, 'region_id' => 1, 'name' => 'Brescia', 'code' => 'BS'],
        ['id' => 12, 'region_id' => 1, 'name' => 'Cremona', 'code' => 'CR'],
        ['id' => 20, 'region_id' => 2, 'name' => 'Campobasso', 'code' => 'CB'],
    ]);
    DB::table('cai_trail_sectors')->insert([
        ['id' => 100, 'province_id' => 10, 'code' => 'BG01', 'full_code' => 'LBG01', 'name' => 'Val Brembana', 'geometry' => null],
        ['id' => 101, 'province_id' => 10, 'code' => 'BG02', 'full_code' => 'LBG02', 'name' => 'Pianura', 'geometry' => null],
        ['id' => 102, 'province_id' => 11, 'code' => 'BS01', 'full_code' => 'LBS01', 'name' => 'Val Camonica', 'geometry' => null],
        ['id' => 103, 'province_id' => 12, 'code' => 'CR01', 'full_code' => 'LCR01', 'name' => 'Cremonese', 'geometry' => null],
        ['id' => 200, 'province_id' => 20, 'code' => 'CB01', 'full_code' => 'MCB01', 'name' => 'Matese', 'geometry' => null],
    ]);

    $ten = caiTrail(['ref' => '10', 'name' => 'Sentiero delle Orobie', 'length_km' => 10, 'status' => CaiTrailStatus::Validated], ['9300001']);
    $two = caiTrail(['ref' => '2', 'name' => 'Sentiero dei Laghi', 'length_km' => 6, 'status' => CaiTrailStatus::PreValidated], ['9300001', '9300002']);
    $twoA = caiTrail(['ref' => '2A', 'name' => null, 'from_place' => 'Passo Gavia', 'length_km' => 4, 'status' => CaiTrailStatus::Difficulty,
        'practicability' => CaiTrailPracticability::WithDifficulty]);
    $e1 = caiTrail(['ref' => 'E1', 'name' => 'Alta Via', 'length_km' => 8, 'status' => CaiTrailStatus::Reconnaissance,
        'practicability' => CaiTrailPracticability::Unknown], ['9230001', '9999999']);
    $five = caiTrail(['ref' => '5', 'length_km' => 20, 'status' => CaiTrailStatus::Validated], ['9300002']);
    $none = caiTrail(['ref' => '', 'length_km' => 3, 'status' => CaiTrailStatus::Reconnaissance]);
    $molise = caiTrail(['ref' => 'M1', 'length_km' => 7], ['9720001']);

    $links = [
        [$ten->id, 1, null, null, 100], [$ten->id, 1, 10, null, 100], [$ten->id, 1, 10, 100, 100],
        [$two->id, 1, null, null, 100], [$two->id, 1, 10, null, 100], [$two->id, 1, 10, 100, 100],
        [$twoA->id, 1, null, null, 80], [$twoA->id, 1, 11, null, 80], [$twoA->id, 1, 11, 102, 80],
        [$e1->id, 1, null, null, 50], [$e1->id, 1, 11, null, 50], [$e1->id, 1, 11, 102, 40], [$e1->id, 1, 10, 100, 10],
        [$five->id, 1, null, null, 30], [$five->id, 1, 10, null, 30], [$five->id, 1, 10, 100, 30],
        [$none->id, 1, null, null, 49.99], [$none->id, 1, 11, null, 49.99], [$none->id, 1, 11, 102, 49.99],
        [$molise->id, 2, null, null, 100], [$molise->id, 2, 20, null, 100], [$molise->id, 2, 20, 200, 100],
    ];

    DB::table('cai_trail_territory')->insert(array_map(fn (array $l): array => [
        'cai_trail_id' => $l[0], 'region_id' => $l[1], 'province_id' => $l[2], 'sector_id' => $l[3], 'percentage' => $l[4],
    ], $links));

    // km intersecati (calcolati dallo script, qui scelti a mano) e sentieri presenti.
    $km = [
        ['region', 1, 1, 3.2, 1], ['region', 1, 2, 5.5, 1], ['region', 1, 3, 6.0, 1], ['region', 1, 4, 16.0, 1],
        ['province', 10, 2, 0.8, 0], ['province', 10, 3, 6.0, 1], ['province', 10, 4, 16.0, 1],
        ['province', 11, 1, 3.2, 1], ['province', 11, 2, 4.7, 1],
        ['sector', 100, 2, 0.8, 0], ['sector', 100, 3, 6.0, 1], ['sector', 100, 4, 16.0, 1],
        ['sector', 102, 1, 3.2, 1], ['sector', 102, 2, 3.2, 0],
        ['region', 2, 4, 7.0, 1], ['province', 20, 4, 7.0, 1], ['sector', 200, 4, 7.0, 1],
    ];

    DB::table('cai_trail_territory_km')->insert(array_map(fn (array $r): array => [
        'territory_type' => $r[0], 'territory_id' => $r[1], 'status' => $r[2], 'km' => $r[3], 'present_routes' => $r[4],
    ], $km));
}

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
