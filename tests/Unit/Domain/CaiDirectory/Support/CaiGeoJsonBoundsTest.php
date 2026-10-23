<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Support\CaiGeoJsonBounds;

test('the bounds of a MultiLineString cover every point', function (): void {
    $bounds = CaiGeoJsonBounds::of(['type' => 'MultiLineString', 'coordinates' => [
        [[9.61, 45.92], [9.7, 46.01]],
        [[9.55, 45.95], [9.65, 45.9]],
    ]]);

    expect($bounds)->not->toBeNull()
        ->and($bounds?->minLat)->toBe(45.9)
        ->and($bounds?->minLng)->toBe(9.55)
        ->and($bounds?->maxLat)->toBe(46.01)
        ->and($bounds?->maxLng)->toBe(9.7);
});

test('the bounds read features, polygons and JSON strings', function (): void {
    $feature = CaiGeoJsonBounds::of(['type' => 'Feature', 'properties' => ['ref' => '1'], 'geometry' => [
        'type' => 'MultiPolygon', 'coordinates' => [[[[14.1, 41.5], [14.4, 41.5], [14.4, 41.8], [14.1, 41.5]]]],
    ]]);
    $string = CaiGeoJsonBounds::of('{"type":"LineString","coordinates":[[10.1,43.1],[10.2,43.25]]}');

    expect([$feature?->minLat, $feature?->minLng, $feature?->maxLat, $feature?->maxLng])->toBe([41.5, 14.1, 41.8, 14.4])
        ->and([$string?->minLat, $string?->maxLat])->toBe([43.1, 43.25]);
});

test('a geometry without valid coordinates has no bounds', function (mixed $geojson): void {
    expect(CaiGeoJsonBounds::of($geojson))->toBeNull();
})->with([
    'null' => [null],
    'not json' => ['non è json'],
    'empty' => [['type' => 'MultiLineString', 'coordinates' => []]],
    'out of range' => [['type' => 'LineString', 'coordinates' => [[25614, 4500000]]]],
]);
