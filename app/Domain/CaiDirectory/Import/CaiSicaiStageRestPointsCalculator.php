<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import;

use App\Domain\CaiDirectory\Support\CaiPolylineDistance;
use Illuminate\Support\Facades\DB;

/**
 * Calcola `cai_sicai_stage_rest_point` (US-1093), riga `punti_appoggio_vicini_sicai`: per ogni tappa SICAI i punti di
 * appoggio entro {@see self::MAX_DISTANCE_M} dal tracciato del sentiero, con distanza e posizione lungo il tracciato
 * ({@see CaiPolylineDistance}). Va eseguito DOPO {@see CaiSicaiStagesImporter} e {@see CaiSicaiRestPointsImporter} e
 * ricalcolato a ogni import dalle tabelle dell'app (non dal datapack): crea, aggiorna ed elimina i legami, in una
 * transazione; un secondo run non scrive nulla. `--dry-run` conta senza scrivere (sui dati già importati). Il riquadro
 * del sentiero (`cai_trails.min_lat`…`max_lng`) allargato di 1,5 km scarta subito i punti lontani.
 */
final class CaiSicaiStageRestPointsCalculator
{
    public const RESULT_KEY = 'punti_appoggio_vicini_sicai';

    public const MAX_DISTANCE_M = 1500;

    private const METERS_PER_DEGREE_LAT = 111195.0;

    /**
     * @return array<string, CaiImportTableResult>
     */
    public function import(bool $dryRun): array
    {
        $run = fn (): CaiImportTableResult => $this->run($dryRun);

        return [self::RESULT_KEY => $dryRun ? $run() : DB::transaction($run)];
    }

    /**
     * Legami calcolati, per chiave `tappa:punto`.
     *
     * @return array<string, array{cai_trail_id: int, rest_point_id: int, distance_m: int, position: int}>
     */
    public function calculate(): array
    {
        $points = DB::table('cai_sicai_rest_points')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('id')
            ->get(['id', 'latitude', 'longitude'])
            ->map(fn (object $point): array => ['id' => (int) $point->id, 'lat' => (float) $point->latitude, 'lng' => (float) $point->longitude])
            ->all();

        $links = [];

        if ($points === []) {
            return $links;
        }

        $stages = DB::table('cai_sicai_stages')
            ->join('cai_trails', 'cai_trails.id', '=', 'cai_sicai_stages.cai_trail_id')
            ->whereNotNull('cai_trails.geometry')
            ->orderBy('cai_sicai_stages.cai_trail_id')
            ->select(['cai_sicai_stages.cai_trail_id', 'cai_trails.geometry', 'cai_trails.min_lat', 'cai_trails.min_lng', 'cai_trails.max_lat', 'cai_trails.max_lng'])
            ->cursor();

        foreach ($stages as $stage) {
            $geometry = json_decode((string) $stage->geometry, true);

            if (! is_array($geometry)) {
                continue;
            }

            $trailId = (int) $stage->cai_trail_id;

            foreach ($points as $point) {
                if (! $this->withinBounds($stage, $point['lat'], $point['lng'])) {
                    continue;
                }

                $measure = CaiPolylineDistance::of($geometry, $point['lat'], $point['lng']);

                if ($measure === null || $measure->distanceM > self::MAX_DISTANCE_M) {
                    continue;
                }

                $links["{$trailId}:{$point['id']}"] = [
                    'cai_trail_id' => $trailId,
                    'rest_point_id' => $point['id'],
                    'distance_m' => (int) round($measure->distanceM),
                    'position' => (int) round($measure->positionM),
                ];
            }
        }

        return $links;
    }

    private function run(bool $dryRun): CaiImportTableResult
    {
        $links = $this->calculate();
        $existing = [];

        foreach (DB::table('cai_sicai_stage_rest_point')->get() as $row) {
            $existing["{$row->cai_trail_id}:{$row->rest_point_id}"] = $row;
        }

        $inserts = [];
        $updated = 0;
        $skipped = 0;

        foreach ($links as $key => $link) {
            $current = $existing[$key] ?? null;

            if ($current === null) {
                $inserts[] = $link;

                continue;
            }

            if ((int) $current->distance_m === $link['distance_m'] && (int) $current->position === $link['position']) {
                $skipped++;

                continue;
            }

            if (! $dryRun) {
                DB::table('cai_sicai_stage_rest_point')
                    ->where('cai_trail_id', $link['cai_trail_id'])
                    ->where('rest_point_id', $link['rest_point_id'])
                    ->update(['distance_m' => $link['distance_m'], 'position' => $link['position']]);
            }
            $updated++;
        }

        $stale = array_values(array_diff_key($existing, $links));

        if (! $dryRun) {
            foreach (array_chunk($inserts, 500) as $chunk) {
                DB::table('cai_sicai_stage_rest_point')->insert($chunk);
            }

            foreach ($stale as $row) {
                DB::table('cai_sicai_stage_rest_point')
                    ->where('cai_trail_id', $row->cai_trail_id)
                    ->where('rest_point_id', $row->rest_point_id)
                    ->delete();
            }
        }

        return new CaiImportTableResult(
            read: count($links),
            created: count($inserts),
            updated: $updated,
            skipped: $skipped,
            deleted: count($stale),
        );
    }

    /**
     * Il punto sta nel riquadro del sentiero allargato della distanza massima; senza riquadro si misura comunque.
     */
    private function withinBounds(object $stage, float $lat, float $lng): bool
    {
        if ($stage->min_lat === null || $stage->min_lng === null || $stage->max_lat === null || $stage->max_lng === null) {
            return true;
        }

        $marginLat = self::MAX_DISTANCE_M / self::METERS_PER_DEGREE_LAT;
        $marginLng = $marginLat / max(0.01, cos(deg2rad($lat)));

        return $lat >= (float) $stage->min_lat - $marginLat
            && $lat <= (float) $stage->max_lat + $marginLat
            && $lng >= (float) $stage->min_lng - $marginLng
            && $lng <= (float) $stage->max_lng + $marginLng;
    }
}
