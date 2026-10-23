<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Queries;

use App\Domain\CaiDirectory\Enums\CaiTrailPracticability;
use App\Domain\CaiDirectory\Models\CaiSicaiRestPoint;
use App\Domain\CaiDirectory\Models\CaiSicaiStage;
use App\Domain\CaiDirectory\Models\CaiTrail;
use App\Domain\CaiDirectory\Support\CaiGeoJsonSimplifier;
use App\Domain\CaiDirectory\Support\CaiOsm2caiRegionName;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupSicaiData;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupSicaiRestPointRow;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupSicaiStageRow;
use App\Domain\Identity\Enums\Region;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Tappe del Sentiero Italia CAI e punti di appoggio di un Gruppo Regionale (Fase 10.9), dalle tabelle importate dal
 * datapack (nessuna chiamata esterna):
 *
 * - **tappe** = quelle delle lettere della regione ({@see Region::sicaiLetters()}: le lettere condivise L, N, C valgono per
 *   entrambe le regioni), in ordine di `sort_key` (lettera, numero, variante, le senza numero in fondo alla lettera);
 * - **varianti**: nella stessa lettera, ogni tappa con lo stesso numero di una precedente (D18S dopo D18, D10S dopo D10N:
 *   in Lombardia e Trentino i tratti N/S sono paralleli e nessuno dei due ha il codice "puro"); le tappe senza numero
 *   (AVxx liguri) sono tutte principali;
 * - **punti di appoggio** = quelli con `region` della fonte uguale alla regione (confronto di {@see CaiOsm2caiRegionName}:
 *   la fonte scrive "Friuli Venezia Giulia") **oppure** entro 1,5 km da almeno una tappa del GR (US-1093), per tipo.
 *
 * Cinque query qualunque sia il numero di tappe o di punti.
 */
final class CaiRegionalGroupSicai
{
    /** Colonne di `cai_trails` dell'elenco e del riepilogo (non la geometria). */
    private const array TRAIL_COLUMNS = ['id', 'osmid', 'ref', 'name', 'from_place', 'to_place', 'cai_scale', 'length_km',
        'ascent', 'descent', 'status', 'practicability'];

    /** Colonne dei punti di appoggio che servono alla pagina (non `fields`, che contiene i dati personali del gestore). */
    private const array REST_POINT_COLUMNS = ['id', 'name', 'type', 'latitude', 'longitude', 'region', 'municipality',
        'address', 'operator', 'website', 'email', 'phone', 'cai_hut_id_cai'];

    /** Tipi della fonte nell'ordine del design; gli altri seguono in ordine alfabetico, "Altro" (tipo vuoto) in fondo. */
    private const array TYPE_ORDER = ['Rifugio', 'Hotel', 'B&B', 'Casa vacanze', 'Ostello', 'Camping'];

    public const string OTHER_TYPE = 'Altro';

    public static function forRegion(Region $region): CaiRegionalGroupSicaiData
    {
        $rows = self::stageRows($region);
        $main = array_values(array_filter($rows, fn (CaiRegionalGroupSicaiStageRow $row): bool => ! $row->isVariant));
        $variants = array_values(array_filter($rows, fn (CaiRegionalGroupSicaiStageRow $row): bool => $row->isVariant));

        $notPracticable = self::withPracticability($rows, CaiTrailPracticability::NotPracticable);
        $withDifficulty = self::withPracticability($rows, CaiTrailPracticability::WithDifficulty);

        $restPointsByType = self::restPointsByType($region, $rows);

        return new CaiRegionalGroupSicaiData(
            groupName: CaiRegionalGroupHuts::groupName($region),
            letters: $region->sicaiLetters(),
            stages: $rows,
            mainCount: count($main),
            variantsCount: count($variants),
            mainKm: self::sumKm($main),
            variantsKm: self::sumKm($variants),
            mainAscent: self::sum($main, 'ascent'),
            mainDescent: self::sum($main, 'descent'),
            mainWithAscentCount: count(array_filter($main, fn (CaiRegionalGroupSicaiStageRow $row): bool => $row->stage->trail?->ascent !== null)),
            notPracticable: $notPracticable,
            withDifficulty: $withDifficulty,
            problemKm: self::sumKm([...$notPracticable, ...$withDifficulty]),
            restPointsByType: $restPointsByType,
            restPointsCount: array_sum(array_map('count', $restPointsByType)),
        );
    }

    /**
     * Tappe delle lettere della regione, in ordine, con varianti già riconosciute e il numero di punti vicini.
     *
     * @return list<CaiRegionalGroupSicaiStageRow>
     */
    public static function stageRows(Region $region): array
    {
        $stages = CaiSicaiStage::query()
            ->whereIn('letter', $region->sicaiLetters())
            ->with(['trail' => fn (Relation $trail) => $trail->select(self::TRAIL_COLUMNS)])
            ->withCount('restPoints')
            ->orderBy('sort_key')
            ->orderBy('cai_trail_id')
            ->get();

        /** @var array<string, string> $mainByNumber "lettera.numero" → codice della prima tappa con quel numero */
        $mainByNumber = [];
        /** @var array<string, list<string>> $variantsOf codice principale → codici delle varianti */
        $variantsOf = [];

        foreach ($stages as $stage) {
            if ($stage->number === null) {
                continue;
            }

            $key = $stage->letter.'.'.$stage->number;

            if (! array_key_exists($key, $mainByNumber)) {
                $mainByNumber[$key] = $stage->code;
            } else {
                $variantsOf[$mainByNumber[$key]][] = $stage->code;
            }
        }

        $rows = [];

        foreach ($stages as $stage) {
            $mainCode = $stage->number === null ? null : $mainByNumber[$stage->letter.'.'.$stage->number];
            $isVariant = $mainCode !== null && $mainCode !== $stage->code;

            $rows[] = new CaiRegionalGroupSicaiStageRow(
                stage: $stage,
                isVariant: $isVariant,
                mainCode: $isVariant ? $mainCode : null,
                variantCodes: $isVariant ? [] : ($variantsOf[$stage->code] ?? []),
                restPointsCount: (int) $stage->getAttribute('rest_points_count'),
            );
        }

        return $rows;
    }

    /**
     * Tracciati delle tappe per la mappa del GR (US-1095), semplificati per lo zoom dato ({@see CaiGeoJsonSimplifier}:
     * le 82 tappe del Piemonte passano da ~940 KB a ~240 KB a zoom 12). Una query; le tappe senza geometria mancano.
     *
     * @param  list<CaiRegionalGroupSicaiStageRow>  $rows
     * @return array<int, array<string, mixed>> id del sentiero → GeoJSON
     */
    public static function geometries(array $rows, int $zoom): array
    {
        $ids = array_map(fn (CaiRegionalGroupSicaiStageRow $row): int => $row->stage->cai_trail_id, $rows);

        if ($ids === []) {
            return [];
        }

        $tolerance = CaiGeoJsonSimplifier::toleranceForZoom($zoom);
        $geometries = [];

        foreach (CaiTrail::query()->whereIn('id', $ids)->whereNotNull('geometry')->get(['id', 'geometry']) as $trail) {
            if ($trail->geometry !== null && $trail->geometry !== []) {
                $geometries[$trail->id] = CaiGeoJsonSimplifier::simplify($trail->geometry, $tolerance);
            }
        }

        return $geometries;
    }

    /**
     * Punti di appoggio del GR raggruppati per tipo, ciascun gruppo per nome.
     *
     * @param  list<CaiRegionalGroupSicaiStageRow>  $rows
     * @return array<string, list<CaiRegionalGroupSicaiRestPointRow>>
     */
    private static function restPointsByType(Region $region, array $rows): array
    {
        $stageIds = array_map(fn (CaiRegionalGroupSicaiStageRow $row): int => $row->stage->cai_trail_id, $rows);
        $regionValues = self::sourceRegionValues($region);

        if ($stageIds === [] && $regionValues === []) {
            return [];
        }

        $points = CaiSicaiRestPoint::query()
            ->where(fn (Builder $query) => $query
                ->whereIn('region', $regionValues)
                ->orWhereHas('stages', fn (Builder $stages) => $stages->whereIn('cai_sicai_stage_rest_point.cai_trail_id', $stageIds)))
            ->with(['stages' => fn (Relation $stages) => $stages
                ->whereIn('cai_sicai_stage_rest_point.cai_trail_id', $stageIds)
                ->select(['cai_sicai_stages.cai_trail_id', 'cai_sicai_stages.code', 'cai_sicai_stages.sort_key'])
                ->orderBy('cai_sicai_stages.sort_key')
                ->orderBy('cai_sicai_stages.cai_trail_id')])
            ->orderBy('name')
            ->orderBy('id')
            ->get(self::REST_POINT_COLUMNS);

        $groups = [];

        foreach ($points as $point) {
            $type = trim((string) $point->type);
            $groups[$type === '' ? self::OTHER_TYPE : $type][] = new CaiRegionalGroupSicaiRestPointRow(
                point: $point,
                inRegion: in_array($point->region, $regionValues, true),
                stageCodes: $point->stages->map(fn (CaiSicaiStage $stage): string => $stage->code)->values()->all(),
            );
        }

        uksort($groups, self::compareTypes(...));

        return $groups;
    }

    /**
     * Valori di `cai_sicai_rest_points.region` che corrispondono alla regione (pochi valori distinti, confronto in PHP).
     *
     * @return list<string>
     */
    private static function sourceRegionValues(Region $region): array
    {
        return CaiSicaiRestPoint::query()
            ->whereNotNull('region')
            ->distinct()
            ->pluck('region')
            ->map(fn (mixed $value): string => (string) $value)
            ->filter(fn (string $value): bool => CaiOsm2caiRegionName::toRegion($value) === $region)
            ->values()
            ->all();
    }

    private static function compareTypes(string $a, string $b): int
    {
        $rank = function (string $type): int {
            $index = array_search($type, self::TYPE_ORDER, true);

            return match (true) {
                $index !== false => $index,
                $type === self::OTHER_TYPE => PHP_INT_MAX,
                default => count(self::TYPE_ORDER),
            };
        };

        return [$rank($a), $a] <=> [$rank($b), $b];
    }

    /**
     * @param  list<CaiRegionalGroupSicaiStageRow>  $rows
     * @return list<CaiRegionalGroupSicaiStageRow>
     */
    private static function withPracticability(array $rows, CaiTrailPracticability $practicability): array
    {
        return array_values(array_filter(
            $rows,
            fn (CaiRegionalGroupSicaiStageRow $row): bool => $row->stage->trail?->practicability === $practicability,
        ));
    }

    /** @param  list<CaiRegionalGroupSicaiStageRow>  $rows */
    private static function sumKm(array $rows): float
    {
        return round(array_sum(array_map(fn (CaiRegionalGroupSicaiStageRow $row): float => (float) $row->stage->trail?->length_km, $rows)), 3);
    }

    /** @param  list<CaiRegionalGroupSicaiStageRow>  $rows */
    private static function sum(array $rows, string $column): int
    {
        return array_sum(array_map(fn (CaiRegionalGroupSicaiStageRow $row): int => (int) $row->stage->trail?->getAttribute($column), $rows));
    }
}
