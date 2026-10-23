<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

/**
 * Riquadro (bbox WGS84) di una geometria GeoJSON (US-1064): coordinate `[lng, lat]` a qualunque profondità
 * (`LineString`, `MultiLineString`, `Polygon`, `MultiPolygon`, `Feature`, `GeometryCollection`). Usato dall'import dei
 * sentieri (colonne `min_lat`…`max_lng` di `cai_trails`) e dall'endpoint dei sentieri in vista.
 */
final readonly class CaiGeoJsonBounds
{
    public function __construct(
        public float $minLat,
        public float $minLng,
        public float $maxLat,
        public float $maxLng,
    ) {}

    /**
     * Riquadro della geometria (array o stringa JSON), `null` se non contiene coordinate valide.
     *
     * @param  array<array-key, mixed>|string|null  $geojson
     */
    public static function of(array|string|null $geojson): ?self
    {
        if (is_string($geojson)) {
            $geojson = json_decode($geojson, true);
        }

        if (! is_array($geojson)) {
            return null;
        }

        $bounds = [INF, INF, -INF, -INF];
        self::walk($geojson, $bounds);

        return is_finite($bounds[0]) ? new self($bounds[1], $bounds[0], $bounds[3], $bounds[2]) : null;
    }

    /**
     * @param  array<array-key, mixed>  $node
     * @param  array{0: float, 1: float, 2: float, 3: float}  $bounds  min lng, min lat, max lng, max lat
     */
    private static function walk(array $node, array &$bounds): void
    {
        foreach (['coordinates', 'geometry', 'geometries', 'features'] as $key) {
            if (isset($node[$key]) && is_array($node[$key])) {
                self::walk($node[$key], $bounds);

                return;
            }
        }

        if (isset($node[0], $node[1]) && is_numeric($node[0]) && is_numeric($node[1])) {
            [$lng, $lat] = [(float) $node[0], (float) $node[1]];

            if (abs($lat) <= 90 && abs($lng) <= 180) {
                $bounds = [min($bounds[0], $lng), min($bounds[1], $lat), max($bounds[2], $lng), max($bounds[3], $lat)];
            }

            return;
        }

        foreach ($node as $child) {
            if (is_array($child)) {
                self::walk($child, $bounds);
            }
        }
    }
}
