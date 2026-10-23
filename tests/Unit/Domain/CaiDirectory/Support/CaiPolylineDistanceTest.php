<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Support\CaiPolylineDistance;

/** Gradi di latitudine corrispondenti a `$meters` metri verso nord. */
function metersNorth(float $meters): float
{
    return $meters / 111195.0;
}

/** Tracciato lungo il parallelo 45° da lng 10,00 a 10,10 (circa 7,86 km). */
function parallelTrack(): array
{
    return ['type' => 'LineString', 'coordinates' => [[10.0, 45.0], [10.05, 45.0], [10.1, 45.0]]];
}

test('a point on the track is at zero distance, at its progressive position', function (): void {
    $measure = CaiPolylineDistance::of(parallelTrack(), 45.0, 10.05);
    $total = CaiPolylineDistance::of(parallelTrack(), 45.0, 10.1);

    expect($measure?->distanceM)->toBeLessThan(0.01)
        ->and($measure?->positionM)->toEqualWithDelta(3931.0, 5.0)
        ->and($total?->positionM)->toEqualWithDelta(7862.0, 10.0);
});

test('measures the perpendicular distance from the nearest segment: 1.4 km inside, 1.6 km outside the 1.5 km threshold', function (): void {
    $near = CaiPolylineDistance::of(parallelTrack(), 45.0 + metersNorth(1400), 10.03);
    $far = CaiPolylineDistance::of(parallelTrack(), 45.0 - metersNorth(1600), 10.07);

    expect($near?->distanceM)->toEqualWithDelta(1400.0, 3.0)
        ->and($near?->distanceM)->toBeLessThanOrEqual(1500.0)
        ->and($near?->positionM)->toEqualWithDelta(2359.0, 5.0)
        ->and($far?->distanceM)->toEqualWithDelta(1600.0, 3.0)
        ->and($far?->distanceM)->toBeGreaterThan(1500.0);
});

test('a point beyond the end of the track is measured from the last vertex', function (): void {
    $measure = CaiPolylineDistance::of(parallelTrack(), 45.0, 10.1 + 1000 / (111195.0 * cos(deg2rad(45.0))));

    expect($measure?->distanceM)->toEqualWithDelta(1000.0, 2.0)
        ->and($measure?->positionM)->toEqualWithDelta(7862.0, 10.0);
});

test('positions follow the direction of travel, across the parts of a MultiLineString', function (): void {
    $track = ['type' => 'MultiLineString', 'coordinates' => [
        [[10.0, 45.0], [10.05, 45.0]],
        [[10.05, 45.0], [10.05, 45.05]],
    ]];

    $positions = collect([
        'end' => [45.04, 10.0505],
        'start' => [45.0 - metersNorth(200), 10.01],
        'middle' => [45.0 + metersNorth(300), 10.04],
    ])->map(fn (array $point): float => CaiPolylineDistance::of($track, $point[0], $point[1])?->positionM ?? -1.0)->sort()->keys()->all();

    expect($positions)->toBe(['start', 'middle', 'end'])
        ->and(CaiPolylineDistance::of($track, 45.04, 10.05)?->positionM)->toEqualWithDelta(3931.0 + 4448.0, 10.0);
});

test('a reversed track reverses the positions', function (): void {
    $reversed = ['type' => 'LineString', 'coordinates' => array_reverse(parallelTrack()['coordinates'])];

    expect(CaiPolylineDistance::of($reversed, 45.0, 10.08)?->positionM)->toEqualWithDelta(1572.0, 5.0);
});

test('without a usable geometry there is no measure', function (mixed $geometry): void {
    expect(CaiPolylineDistance::of($geometry, 45.0, 10.0))->toBeNull();
})->with([
    'null' => [null],
    'point' => [['type' => 'Point', 'coordinates' => [10.0, 45.0]]],
    'empty' => [['type' => 'MultiLineString', 'coordinates' => []]],
]);
