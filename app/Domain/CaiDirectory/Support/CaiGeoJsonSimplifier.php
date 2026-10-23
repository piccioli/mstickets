<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

/**
 * Semplificazione di una geometria lineare GeoJSON per la mappa (US-1064): Douglas–Peucker con tolleranza in gradi e
 * coordinate arrotondate. I sentieri osm2cai hanno un punto ogni ~27 m, mentre a zoom 11 un pixel vale ~50 m: con mezza
 * cella di pixel di tolleranza ({@see toleranceForZoom()}) il disegno non cambia e il JSON della mappa del GR si riduce
 * di diverse volte. Tipi diversi da `LineString`/`MultiLineString` restano come sono.
 */
final class CaiGeoJsonSimplifier
{
    /** Tolleranza in gradi pari a mezzo pixel (tile da 256 px) allo zoom dato. */
    public static function toleranceForZoom(int $zoom): float
    {
        return 0.5 * 360 / (256 * 2 ** $zoom);
    }

    /**
     * @param  array<string, mixed>  $geojson
     * @return array<string, mixed>
     */
    public static function simplify(array $geojson, float $tolerance, int $decimals = 5): array
    {
        $coordinates = $geojson['coordinates'] ?? null;

        if (! is_array($coordinates)) {
            return $geojson;
        }

        return match ($geojson['type'] ?? null) {
            'LineString' => [...$geojson, 'coordinates' => self::line($coordinates, $tolerance, $decimals)],
            'MultiLineString' => [...$geojson, 'coordinates' => array_values(array_map(
                fn (mixed $line): array => is_array($line) ? self::line($line, $tolerance, $decimals) : [],
                $coordinates,
            ))],
            default => $geojson,
        };
    }

    /**
     * @param  array<array-key, mixed>  $points
     * @return list<array{0: float, 1: float}>
     */
    private static function line(array $points, float $tolerance, int $decimals): array
    {
        $points = array_values(array_filter(
            array_map(fn (mixed $point): ?array => is_array($point) && isset($point[0], $point[1]) && is_numeric($point[0]) && is_numeric($point[1])
                ? [(float) $point[0], (float) $point[1]]
                : null, $points),
        ));

        if (count($points) > 2) {
            $keep = array_fill(0, count($points), false);
            $keep[0] = $keep[count($points) - 1] = true;
            self::mark($points, 0, count($points) - 1, $tolerance * $tolerance, $keep);
            $points = array_values(array_filter($points, fn (int $index): bool => $keep[$index], ARRAY_FILTER_USE_KEY));
        }

        return array_map(fn (array $point): array => [round($point[0], $decimals), round($point[1], $decimals)], $points);
    }

    /**
     * Douglas–Peucker iterativo (nessuna ricorsione profonda sulle linee lunghe).
     *
     * @param  list<array{0: float, 1: float}>  $points
     * @param  array<int, bool>  $keep
     */
    private static function mark(array $points, int $first, int $last, float $toleranceSquared, array &$keep): void
    {
        $stack = [[$first, $last]];

        while ($stack !== []) {
            [$start, $end] = array_pop($stack);
            $farthest = null;
            $max = $toleranceSquared;

            for ($i = $start + 1; $i < $end; $i++) {
                $distance = self::segmentDistanceSquared($points[$i], $points[$start], $points[$end]);

                if ($distance > $max) {
                    $max = $distance;
                    $farthest = $i;
                }
            }

            if ($farthest !== null) {
                $keep[$farthest] = true;
                $stack[] = [$start, $farthest];
                $stack[] = [$farthest, $end];
            }
        }
    }

    /**
     * @param  array{0: float, 1: float}  $point
     * @param  array{0: float, 1: float}  $a
     * @param  array{0: float, 1: float}  $b
     */
    private static function segmentDistanceSquared(array $point, array $a, array $b): float
    {
        [$dx, $dy] = [$b[0] - $a[0], $b[1] - $a[1]];
        $length = $dx * $dx + $dy * $dy;
        $t = $length > 0 ? max(0.0, min(1.0, (($point[0] - $a[0]) * $dx + ($point[1] - $a[1]) * $dy) / $length)) : 0.0;
        [$x, $y] = [$a[0] + $t * $dx - $point[0], $a[1] + $t * $dy - $point[1]];

        return $x * $x + $y * $y;
    }
}
