<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Enums\CaiTrailPracticability;
use App\Domain\CaiDirectory\Models\CaiSicaiRestPoint;
use App\Domain\CaiDirectory\Models\CaiSicaiStage;
use App\Domain\CaiDirectory\Queries\CaiRegionalGroupSicai;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupSicaiData;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupSicaiRestPointRow;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupSicaiStageRow;
use App\Domain\CaiDirectory\Support\CaiSicaiStageCode;
use App\Domain\Identity\Enums\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Tappa SICAI di fixture con il suo sentiero; `$code` come nel nome OSM ("E03", "D18S") o un `ref` senza numero ("AV16",
 * con `$letter`).
 *
 * @param  array<string, mixed>  $trail
 */
function grSicaiStage(string $code, array $trail = [], ?string $letter = null): CaiSicaiStage
{
    $parsed = $letter === null
        ? CaiSicaiStageCode::parse('Sentiero Italia - Tappa '.$code, null, null, null)
        : CaiSicaiStageCode::parse(null, null, "Sentiero Italia - Liguria {$letter}00", $code);
    $sentiero = caiTrail(array_merge(['name' => 'Sentiero Italia - Tappa '.$code], $trail));

    return CaiSicaiStage::create([
        'cai_trail_id' => $sentiero->id,
        'itinerary_name' => 'Sentiero Italia',
        'letter' => $parsed->letter,
        'number' => $parsed->number,
        'variant' => $parsed->variant,
        'code' => $parsed->code,
        'sort_key' => $parsed->sortKey,
    ]);
}

/** Punto di appoggio di fixture, vicino (pivot) alle tappe indicate. */
function grSicaiRestPoint(int $id, string $name, ?string $region, ?string $type = 'Rifugio', array $nearStages = []): CaiSicaiRestPoint
{
    $point = CaiSicaiRestPoint::create([
        'id' => $id,
        'name' => $name,
        'type' => $type,
        'latitude' => 44.1,
        'longitude' => 7.5,
        'region' => $region,
        'fields' => ['type' => $type],
    ]);

    foreach (array_values($nearStages) as $position => $stage) {
        DB::table('cai_sicai_stage_rest_point')->insert([
            'cai_trail_id' => $stage->cai_trail_id,
            'rest_point_id' => $id,
            'distance_m' => 100,
            'position' => $position * 1000,
        ]);
    }

    return $point;
}

/** @return list<string> */
function grSicaiCodes(CaiRegionalGroupSicaiData $data): array
{
    return array_map(fn (CaiRegionalGroupSicaiStageRow $row): string => $row->stage->code, $data->stages);
}

/** @return array<string, list<string>> */
function grSicaiPointNames(CaiRegionalGroupSicaiData $data): array
{
    return array_map(
        fn (array $rows): array => array_map(fn (CaiRegionalGroupSicaiRestPointRow $row): string => $row->point->name, $rows),
        $data->restPointsByType,
    );
}

test('Toscana and Emilia-Romagna both see every L stage, and other letters stay out', function (): void {
    grSicaiStage('L01');
    grSicaiStage('L02');
    grSicaiStage('N01');
    grSicaiStage('E01');

    expect(grSicaiCodes(CaiRegionalGroupSicai::forRegion(Region::Toscana)))->toBe(['L01', 'L02'])
        ->and(grSicaiCodes(CaiRegionalGroupSicai::forRegion(Region::EmiliaRomagna)))->toBe(['L01', 'L02'])
        ->and(grSicaiCodes(CaiRegionalGroupSicai::forRegion(Region::Umbria)))->toBe(['N01'])
        ->and(grSicaiCodes(CaiRegionalGroupSicai::forRegion(Region::Marche)))->toBe(['N01'])
        ->and(CaiRegionalGroupSicai::forRegion(Region::Toscana)->letters)->toBe(['L']);
});

test('stages are ordered by number and variant, with unnumbered stages last', function (): void {
    grSicaiStage('AV16', letter: 'G');
    grSicaiStage('G28V');
    grSicaiStage('G02');
    grSicaiStage('AV02', letter: 'G');
    grSicaiStage('G01');
    grSicaiStage('G01S');

    expect(grSicaiCodes(CaiRegionalGroupSicai::forRegion(Region::Liguria)))->toBe(['G01', 'G01S', 'G02', 'G28V', 'AV02', 'AV16']);
});

test('a stage with the same number as an earlier one is a variant of it, even without a plain code', function (): void {
    grSicaiStage('D18');
    grSicaiStage('D18S');
    grSicaiStage('D10N');
    grSicaiStage('D10S');
    grSicaiStage('D11N');
    grSicaiStage('D11SA');
    grSicaiStage('D11SB');

    $rows = collect(CaiRegionalGroupSicai::forRegion(Region::Lombardia)->stages)->keyBy(fn (CaiRegionalGroupSicaiStageRow $row): string => $row->stage->code);

    expect($rows['D10N']->isVariant)->toBeFalse()
        ->and($rows['D10N']->variantCodes)->toBe(['D10S'])
        ->and($rows['D10S']->isVariant)->toBeTrue()
        ->and($rows['D10S']->mainCode)->toBe('D10N')
        ->and($rows['D11N']->variantCodes)->toBe(['D11SA', 'D11SB'])
        ->and($rows['D18']->variantCodes)->toBe(['D18S'])
        ->and($rows['D18S']->mainCode)->toBe('D18');
});

test('the summary counts main stages and variants apart, with not practicable and difficult stages', function (): void {
    grSicaiStage('E01', ['length_km' => 10.25, 'ascent' => 800, 'descent' => 300]);
    grSicaiStage('E02', ['length_km' => 12.5, 'ascent' => 500, 'descent' => 900, 'issues_status' => 'non percorribile',
        'practicability' => CaiTrailPracticability::NotPracticable]);
    grSicaiStage('E02S', ['length_km' => 14.0, 'ascent' => 600, 'descent' => 950, 'issues_status' => 'percorribile parzialmente',
        'practicability' => CaiTrailPracticability::WithDifficulty]);
    grSicaiStage('E03', ['length_km' => 8.0, 'ascent' => 400, 'descent' => 450]);
    grSicaiStage('E04', ['length_km' => 6.0, 'ascent' => null, 'descent' => null]);

    $data = CaiRegionalGroupSicai::forRegion(Region::Piemonte);

    expect($data->stagesCount())->toBe(5)
        ->and($data->mainCount)->toBe(4)
        ->and($data->variantsCount)->toBe(1)
        ->and($data->mainKm)->toBe(36.75)
        ->and($data->variantsKm)->toBe(14.0)
        ->and($data->mainAscent)->toBe(1700)
        ->and($data->mainDescent)->toBe(1650)
        ->and($data->mainWithAscentCount)->toBe(3)
        ->and(array_map(fn (CaiRegionalGroupSicaiStageRow $row): string => $row->stage->code, $data->notPracticable))->toBe(['E02'])
        ->and(array_map(fn (CaiRegionalGroupSicaiStageRow $row): string => $row->stage->code, $data->withDifficulty))->toBe(['E02S'])
        ->and($data->problemKm)->toBe(26.5)
        ->and($data->groupName)->toBe('GR Piemonte');
});

test('rest points of the group are those in the region or near a group stage, by type, with the near stages', function (): void {
    $l01 = grSicaiStage('L01');
    $l02 = grSicaiStage('L02');
    $e01 = grSicaiStage('E01');
    grSicaiRestPoint(1, 'Rifugio in Toscana lontano', 'Toscana');
    grSicaiRestPoint(2, 'Albergo emiliano sulla L02', 'Emilia-Romagna', 'Hotel', [$l02, $l01]);
    grSicaiRestPoint(3, 'Bivacco in Piemonte', 'Piemonte', 'Bivacco', [$e01]);
    grSicaiRestPoint(4, 'Agriturismo senza regione', null, 'Agriturismo', [$l01]);
    grSicaiRestPoint(5, 'Punto senza tipo', 'Toscana', '');
    grSicaiRestPoint(6, 'Altro rifugio in Toscana', 'Toscana', 'Rifugio', [$l01]);

    $toscana = CaiRegionalGroupSicai::forRegion(Region::Toscana);
    $hotel = $toscana->restPointsByType['Hotel'][0];

    expect(grSicaiPointNames($toscana))->toBe([
        'Rifugio' => ['Altro rifugio in Toscana', 'Rifugio in Toscana lontano'],
        'Hotel' => ['Albergo emiliano sulla L02'],
        'Agriturismo' => ['Agriturismo senza regione'],
        CaiRegionalGroupSicai::OTHER_TYPE => ['Punto senza tipo'],
    ])
        ->and($toscana->restPointsCount)->toBe(5)
        ->and($hotel->inRegion)->toBeFalse()
        ->and($hotel->stageCodes)->toBe(['L01', 'L02'])
        ->and($toscana->restPointsByType['Rifugio'][1]->inRegion)->toBeTrue()
        ->and($toscana->restPointsByType['Rifugio'][1]->stageCodes)->toBe([])
        ->and(collect($toscana->stages)->map(fn (CaiRegionalGroupSicaiStageRow $row): int => $row->restPointsCount)->all())->toBe([3, 1]);

    expect(grSicaiPointNames(CaiRegionalGroupSicai::forRegion(Region::EmiliaRomagna)))->toBe([
        'Rifugio' => ['Altro rifugio in Toscana'],
        'Hotel' => ['Albergo emiliano sulla L02'],
        'Agriturismo' => ['Agriturismo senza regione'],
    ]);
});

test('the source region name is matched loosely, so "Friuli Venezia Giulia" belongs to its group', function (): void {
    grSicaiRestPoint(1, 'Rifugio friulano', 'Friuli Venezia Giulia');

    $data = CaiRegionalGroupSicai::forRegion(Region::FriuliVeneziaGiulia);

    expect(grSicaiPointNames($data))->toBe(['Rifugio' => ['Rifugio friulano']])
        ->and($data->restPointsByType['Rifugio'][0]->inRegion)->toBeTrue();
});

test('a region without stages nor rest points has an empty summary', function (): void {
    grSicaiStage('E01');

    $data = CaiRegionalGroupSicai::forRegion(Region::Basilicata);

    expect($data->stages)->toBe([])
        ->and($data->mainKm)->toBe(0.0)
        ->and($data->restPointsByType)->toBe([])
        ->and($data->restPointsCount)->toBe(0);
});
