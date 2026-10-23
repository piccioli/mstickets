<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

/**
 * Distanza di un punto da un tracciato GeoJSON (`LineString` o `MultiLineString`, coordinate `[lng, lat]`) e posizione
 * lungo il tracciato del suo punto più vicino (US-1093). Proiezione equirettangolare locale centrata sul punto: precisione
 * di pochi metri sotto i 2 km, sufficiente per i punti di appoggio entro 1,5 km dalle tappe SICAI. La posizione è la
 * distanza progressiva dall'inizio del tracciato (le parti di un `MultiLineString` nell'ordine della fonte), con ogni
 * segmento misurato alla sua latitudine media.
 */
final readonly class CaiPolylineDistance
{
    private const EARTH_RADIUS_M = 6371008.8;

    public function __construct(
        public float $distanceM,
        public float $positionM,
    ) {}

    /**
     * `null` se la geometria non contiene almeno un punto valido.
     *
     * @param  array<array-key, mixed>|null  $geometry
     */
    public static function of(?array $geometry, float $lat, float $lng): ?self
    {
        $best = null;
        $travelled = 0.0;
        $cosLat = cos(deg2rad($lat));

        foreach (self::lines($geometry) as $line) {
            $previous = null;

            foreach ($line as [$vertexLng, $vertexLat]) {
                // Coordinate locali in metri, origine nel punto cercato.
                $vertex = [
                    deg2rad($vertexLng - $lng) * $cosLat * self::EARTH_RADIUS_M,
                    deg2rad($vertexLat - $lat) * self::EARTH_RADIUS_M,
                    $vertexLat,
                    $vertexLng,
                ];

                if ($previous === null) {
                    $distance = hypot($vertex[0], $vertex[1]);

                    if ($best === null || $distance < $best->distanceM) {
                        $best = new self($distance, $travelled);
                    }

                    $previous = $vertex;

                    continue;
                }

                [$dx, $dy] = [$vertex[0] - $previous[0], $vertex[1] - $previous[1]];
                $squared = $dx * $dx + $dy * $dy;
                $t = $squared > 0.0 ? max(0.0, min(1.0, -($previous[0] * $dx + $previous[1] * $dy) / $squared)) : 0.0;
                $distance = hypot($previous[0] + $t * $dx, $previous[1] + $t * $dy);
                $length = self::segmentLength($previous[2], $previous[3], $vertex[2], $vertex[3]);

                if ($best === null || $distance < $best->distanceM) {
                    $best = new self($distance, $travelled + $t * $length);
                }

                $travelled += $length;
                $previous = $vertex;
            }
        }

        return $best;
    }

    private static function segmentLength(float $fromLat, float $fromLng, float $toLat, float $toLng): float
    {
        $x = deg2rad($toLng - $fromLng) * cos(deg2rad(($fromLat + $toLat) / 2));
        $y = deg2rad($toLat - $fromLat);

        return hypot($x, $y) * self::EARTH_RADIUS_M;
    }

    /**
     * Parti del tracciato come liste di `[lng, lat]` valide.
     *
     * @param  array<array-key, mixed>|null  $geometry
     * @return list<list<array{0: float, 1: float}>>
     */
    private static function lines(?array $geometry): array
    {
        $coordinates = $geometry['coordinates'] ?? null;

        if (! is_array($coordinates)) {
            return [];
        }

        $parts = match ($geometry['type'] ?? null) {
            'LineString' => [$coordinates],
            'MultiLineString' => $coordinates,
            default => [],
        };

        $lines = [];

        foreach ($parts as $part) {
            if (! is_array($part)) {
                continue;
            }

            $line = [];

            foreach ($part as $point) {
                if (is_array($point) && isset($point[0], $point[1]) && is_numeric($point[0]) && is_numeric($point[1])) {
                    $line[] = [(float) $point[0], (float) $point[1]];
                }
            }

            if ($line !== []) {
                $lines[] = $line;
            }
        }

        return $lines;
    }
}
