<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Import\CaiDatapackImporter;
use App\Domain\CaiDirectory\Models\CaiSicaiRestPoint;
use App\Domain\CaiDirectory\Models\CaiSicaiStage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/** Punto di appoggio di fixture nelle coordinate indicate (`[lng, lat]` come nell'API). */
function sicaiRestPointAt(int $id, float $lng, float $lat): array
{
    return [...sicaiRestPointApiDetail($id, 'Punto '.$id), 'geo' => ['type' => 'Point', 'coordinates' => [$lng, $lat]]];
}

/**
 * Datapack con le tappe della fixture osm2cai (101 D18S da 10,1/43,1 a 10,2/43,2; 103 L01AP da 10,5/43,5) e i punti:
 * 1 a metà della D18S, 2 sul suo inizio, 3 a 1,6 km a sud dell'inizio (fuori), 4 sull'inizio della L01AP.
 */
function sicaiNearbyDatapack(?array $points = null, ?string $sqlite = null): string
{
    $sqlite = makeTrailTerritoriesDatapack([], $sqlite);
    writeSicaiRestPointsSnapshot($sqlite, $points ?? [
        sicaiRestPointAt(1, 10.15, 43.15),
        sicaiRestPointAt(2, 10.1, 43.1),
        sicaiRestPointAt(3, 10.1, 43.1 - 1600 / 111195),
        sicaiRestPointAt(4, 10.5, 43.5),
    ]);

    return $sqlite;
}

/** Legami come `tappa:punto:distanza:posizione`, in ordine di tappa e percorrenza. */
function sicaiNearbyRows(): array
{
    return DB::table('cai_sicai_stage_rest_point')->orderBy('cai_trail_id')->orderBy('position')->get()
        ->map(fn (object $row): string => "{$row->cai_trail_id}:{$row->rest_point_id}:{$row->distance_m}:{$row->position}")
        ->all();
}

test('after stages and rest points, links each stage to the points within 1.5 km in order of travel', function (): void {
    $result = app(CaiDatapackImporter::class)->import(sicaiNearbyDatapack(), false)['punti_appoggio_vicini_sicai'];

    expect($result->created)->toBe(3)
        ->and($result->deleted)->toBe(0)
        ->and(sicaiNearbyRows())->toBe(['101:2:0:0', '101:1:0:6882', '103:4:0:0'])
        ->and(CaiSicaiStage::query()->findOrFail(101)->restPoints->pluck('id')->all())->toBe([2, 1])
        ->and(CaiSicaiStage::query()->findOrFail(101)->restPoints->first()?->pivot?->getAttribute('distance_m'))->toBe(0)
        ->and(CaiSicaiRestPoint::query()->findOrFail(4)->stages->pluck('code')->all())->toBe(['L01AP'])
        ->and(CaiSicaiRestPoint::query()->findOrFail(3)->stages)->toHaveCount(0);
});

test('a second run writes nothing', function (): void {
    $sqlite = sicaiNearbyDatapack();
    app(CaiDatapackImporter::class)->import($sqlite, false);

    $result = app(CaiDatapackImporter::class)->import($sqlite, false)['punti_appoggio_vicini_sicai'];

    expect($result->created)->toBe(0)
        ->and($result->updated)->toBe(0)
        ->and($result->deleted)->toBe(0)
        ->and($result->skipped)->toBe(3);
});

test('is recalculated at every import: moved points update, far points are unlinked, gone points take their links', function (): void {
    $sqlite = sicaiNearbyDatapack();
    app(CaiDatapackImporter::class)->import($sqlite, false);

    sicaiNearbyDatapack([
        sicaiRestPointAt(1, 10.15, 43.15 + 1000 / 111195),
        sicaiRestPointAt(2, 10.1, 43.1 - 2000 / 111195),
        sicaiRestPointAt(3, 10.1, 43.1 - 1400 / 111195),
    ], $sqlite);
    $result = app(CaiDatapackImporter::class)->import($sqlite, false)['punti_appoggio_vicini_sicai'];

    expect($result->created)->toBe(1)
        ->and($result->updated)->toBe(1)
        // Il punto 2 si è allontanato; il punto 4, sparito dalla fonte, se ne va in cascata con i suoi legami.
        ->and($result->deleted)->toBe(1)
        ->and(sicaiNearbyRows())->toHaveCount(2)
        ->and(sicaiNearbyRows()[0])->toBe('101:3:1400:0')
        ->and((int) DB::table('cai_sicai_stage_rest_point')->where('rest_point_id', 1)->value('distance_m'))->toBe(589); // 1 km a nord di una linea diagonale: 1000 · 8112 / 13764 m.
});

test('dry-run counts without writing', function (): void {
    $result = app(CaiDatapackImporter::class)->import(sicaiNearbyDatapack(), true)['punti_appoggio_vicini_sicai'];

    expect($result->created)->toBe(0)
        ->and(DB::table('cai_sicai_stage_rest_point')->count())->toBe(0);
});
