<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Support\CaiGeoJsonSimplifier;

test('the tolerance is half a pixel at the zoom and halves at each zoom level', function (): void {
    expect(CaiGeoJsonSimplifier::toleranceForZoom(11))->toEqualWithDelta(0.000343, 0.000001)
        ->and(CaiGeoJsonSimplifier::toleranceForZoom(12))->toEqualWithDelta(CaiGeoJsonSimplifier::toleranceForZoom(11) / 2, 1e-12);
});

test('points closer to the line than the tolerance are dropped, the ends and the corners stay', function (): void {
    $geojson = ['type' => 'MultiLineString', 'coordinates' => [
        [[9.0, 46.0], [9.0001, 46.00001], [9.0002, 46.0], [9.0002, 46.001], [9.00021, 46.002]],
        [[9.1, 46.1], [9.2, 46.2]],
    ]];

    $simplified = CaiGeoJsonSimplifier::simplify($geojson, 0.0001);

    expect($simplified)->toBe(['type' => 'MultiLineString', 'coordinates' => [
        [[9.0, 46.0], [9.0002, 46.0], [9.00021, 46.002]],
        [[9.1, 46.1], [9.2, 46.2]],
    ]]);
});

test('coordinates are rounded to five decimals and a LineString is supported', function (): void {
    expect(CaiGeoJsonSimplifier::simplify(['type' => 'LineString', 'coordinates' => [[9.1234567, 46.7654321], [9.2, 46.8]]], 0.0001))
        ->toBe(['type' => 'LineString', 'coordinates' => [[9.12346, 46.76543], [9.2, 46.8]]]);
});

test('other geometries are returned as they are', function (): void {
    $polygon = ['type' => 'Polygon', 'coordinates' => [[[9.0, 46.0], [9.1, 46.0], [9.0, 46.1], [9.0, 46.0]]]];

    expect(CaiGeoJsonSimplifier::simplify($polygon, 0.1))->toBe($polygon);
});
