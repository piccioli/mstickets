<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Queries;

use App\Domain\CaiDirectory\Enums\CaiTrailPracticability;
use App\Domain\CaiDirectory\Enums\CaiTrailSort;
use App\Domain\CaiDirectory\Enums\CaiTrailStatus;
use App\Domain\CaiDirectory\Enums\CaiTrailTerritoryType;
use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Models\CaiTrail;
use App\Domain\CaiDirectory\Models\CaiTrailProvince;
use App\Domain\CaiDirectory\Models\CaiTrailRegion;
use App\Domain\CaiDirectory\Models\CaiTrailSector;
use App\Domain\CaiDirectory\Models\CaiTrailTerritory;
use App\Domain\CaiDirectory\Support\CaiGeoJsonBounds;
use App\Domain\CaiDirectory\Support\CaiMapArea;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupTrailFilters;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupTrailProvinceRow;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupTrailRow;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupTrailsData;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupTrailSectionRow;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupTrailSectorRow;
use App\Domain\CaiDirectory\Support\CaiTrailKmStats;
use App\Domain\Identity\Enums\Region;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Sentieri di sola lettura di un Gruppo Regionale (Fase 10.7), dalle tabelle importate dal datapack (nessuna
 * intersezione geometrica: le calcola una volta lo script osm2cai):
 *
 * - **km nel territorio regionale**: `cai_trail_territory_km` della regione (km intersecati, anche senza Sezione),
 *   con i sentieri **presenti** (≥ 50% della lunghezza nella regione); stesso dato per province e settori;
 * - **km gestiti dalle Sezioni del GR**: `length_km` dei sentieri legati ad almeno una Sezione con il prefisso del GR
 *   ({@see CaiRegionalGroupDashboardMetrics::sectionsOf()}), ogni sentiero una sola volta;
 * - percorribilità, sentieri senza Sezione / condivisi ed elenco: sui sentieri presenti nella regione;
 * - per Sezione: tutti i sentieri della Sezione (come la 10.6), un sentiero condiviso conta in ciascuna.
 *
 * "Senza Sezione" / "condiviso" contano le Sezioni esistenti in `cai_sections` (qualunque GR), come la scheda del
 * sentiero. Numero di query costante; l'elenco è paginato in SQL.
 */
final class CaiRegionalGroupTrails
{
    /** Colonne di `cai_trails` dell'elenco (non la geometria). */
    private const array LIST_COLUMNS = ['id', 'osmid', 'ref', 'name', 'from_place', 'to_place', 'cai_scale', 'length_km',
        'status', 'practicability'];

    public static function forRegion(Region $region): CaiRegionalGroupTrailsData
    {
        $regionId = self::regionId($region);
        $prefix = CaiRegionalGroupDashboardMetrics::prefixOf($region);

        [$managedKm, $managedCount] = $prefix === null ? [0.0, 0] : self::managed($prefix);
        $present = $regionId === null ? null : self::presentSummary($regionId);

        $practicability = [];

        foreach (CaiTrailPracticability::cases() as $case) {
            $practicability[$case->value] = $present['practicability'][$case->value] ?? ['km' => 0.0, 'count' => 0];
        }

        return new CaiRegionalGroupTrailsData(
            regionId: $regionId,
            codiceCai: $prefix === null ? null : $prefix.'000',
            groupName: CaiRegionalGroupHuts::groupName($region),
            territory: self::territory($regionId),
            managedKm: $managedKm,
            managedCount: $managedCount,
            presentWithoutSectionCount: $present['withoutSectionCount'] ?? 0,
            presentWithoutSectionKm: $present['withoutSectionKm'] ?? 0.0,
            presentSharedCount: $present['sharedCount'] ?? 0,
            practicability: $practicability,
            provinces: $regionId === null ? [] : self::provinces($regionId),
            sections: $prefix === null ? [] : self::sections($prefix),
        );
    }

    /**
     * Elenco paginato dei sentieri presenti nella regione, con filtri, ricerca e ordinamento in SQL. Quattro query (conteggio,
     * pagina, Sezioni, territori della pagina) qualunque sia la dimensione della pagina.
     *
     * @return LengthAwarePaginator<int, CaiRegionalGroupTrailRow>
     */
    public static function trails(Region $region, CaiRegionalGroupTrailFilters $filters, int $perPage = 25, int $page = 1): LengthAwarePaginator
    {
        $regionId = self::regionId($region);

        if ($regionId === null) {
            /** @var LengthAwarePaginator<int, CaiRegionalGroupTrailRow> $empty */
            $empty = new LengthAwarePaginator([], 0, $perPage, 1);

            return $empty;
        }

        $query = self::presentTrails($regionId)
            ->select(self::LIST_COLUMNS)
            ->with(self::rowRelations($regionId));

        self::applyFilters($query, $filters);
        self::applySort($query, $filters);

        $paginator = $query->paginate($perPage, ['*'], 'page', max(1, $page));

        /** @var LengthAwarePaginator<int, CaiRegionalGroupTrailRow> $rows */
        $rows = $paginator->through(fn (CaiTrail $trail): CaiRegionalGroupTrailRow => self::row($trail));

        return $rows;
    }

    /** Km (`length_km`) di tutti i sentieri dell'elenco con questi filtri (non solo della pagina), in una query. */
    public static function trailsKm(Region $region, CaiRegionalGroupTrailFilters $filters): float
    {
        $regionId = self::regionId($region);

        if ($regionId === null) {
            return 0.0;
        }

        $query = self::presentTrails($regionId);
        self::applyFilters($query, $filters);

        return (float) $query->sum('cai_trails.length_km');
    }

    /**
     * Sentieri presenti nella regione il cui riquadro interseca `$bounds` (mappa del GR da zoom 11, US-1064), con la
     * geometria: al massimo `$limit`, i più lunghi per primi (quelli senza lunghezza in fondo); `total` = tutti quelli
     * nel riquadro. Tre query (conteggio, sentieri, Sezioni + territori) qualunque sia il numero di sentieri.
     *
     * @return array{rows: list<CaiRegionalGroupTrailRow>, total: int}
     */
    public static function inBounds(int $regionId, CaiGeoJsonBounds $bounds, int $limit): array
    {
        $query = self::presentTrails($regionId)
            ->whereNotNull('cai_trails.min_lat')
            ->where('cai_trails.min_lat', '<=', $bounds->maxLat)
            ->where('cai_trails.max_lat', '>=', $bounds->minLat)
            ->where('cai_trails.min_lng', '<=', $bounds->maxLng)
            ->where('cai_trails.max_lng', '>=', $bounds->minLng);

        $total = (clone $query)->count();

        $trails = $query
            ->select([...self::LIST_COLUMNS, 'geometry'])
            ->with(self::rowRelations($regionId))
            ->orderByRaw('CASE WHEN cai_trails.length_km IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('cai_trails.length_km')
            ->orderBy('cai_trails.id')
            ->limit(max(0, $limit))
            ->get();

        return [
            'rows' => $trails->map(fn (CaiTrail $trail): CaiRegionalGroupTrailRow => self::row($trail))->values()->all(),
            'total' => $total,
        ];
    }

    /**
     * Settori della mappa del GR (US-1064), dalle province di {@see forRegion()} con la geometria (una query); i settori
     * senza geometria non compaiono.
     *
     * @return list<CaiMapArea>
     */
    public static function sectorAreas(CaiRegionalGroupTrailsData $data, bool $listable = true): array
    {
        $ids = collect($data->provinces)->flatMap(fn (CaiRegionalGroupTrailProvinceRow $province): array => array_map(
            fn (CaiRegionalGroupTrailSectorRow $sector): int => $sector->id,
            $province->sectors,
        ))->all();

        $geometries = CaiTrailSector::query()->whereIn('id', $ids)->pluck('geometry', 'id');
        $areas = [];

        foreach ($data->provinces as $province) {
            foreach ($province->sectors as $sector) {
                $geometry = $geometries->get($sector->id);
                $geometry = is_string($geometry) ? json_decode($geometry, true) : $geometry;
                $area = CaiMapArea::forSector($sector, is_array($geometry) ? $geometry : null, $province->name, $listable);

                if ($area !== null) {
                    $areas[] = $area;
                }
            }
        }

        return $areas;
    }

    /**
     * Km nel territorio regionale (tutti gli stati, anche senza Sezione) per il badge della Dashboard GR (US-1066), 0 se
     * il datapack non contiene la regione o non ha km per lei. Due query.
     */
    public static function territoryKm(Region $region): float
    {
        return round(self::territory(self::regionId($region))->km(), 2);
    }

    /** Id della regione osm2cai abbinata alla regione del GR, `null` se il datapack non la contiene. */
    public static function regionId(Region $region): ?int
    {
        $id = CaiTrailRegion::query()->where('region_key', $region->value)->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * Sentieri presenti (≥ 50% della lunghezza) nella regione osm2cai.
     *
     * @return Builder<CaiTrail>
     */
    public static function presentTrails(int $regionId): Builder
    {
        return CaiTrail::query()->whereIn('cai_trails.id', fn (QueryBuilder $sub) => $sub
            ->select('cai_trail_id')
            ->from('cai_trail_territory')
            ->where('region_id', $regionId)
            ->whereNull('province_id')
            ->whereNull('sector_id')
            ->where('percentage', '>=', 50));
    }

    /** Km e sentieri presenti per stato nella regione osm2cai, vuoti senza regione o senza dati. */
    private static function territory(?int $regionId): CaiTrailKmStats
    {
        return $regionId === null
            ? CaiTrailKmStats::empty()
            : (self::territoryStats(CaiTrailTerritoryType::Region, [$regionId])[$regionId] ?? CaiTrailKmStats::empty());
    }

    /** @return array{0: float, 1: int} km (deduplicati) e numero dei sentieri delle Sezioni del GR */
    private static function managed(string $prefix): array
    {
        $row = CaiTrail::query()
            ->whereIn('id', fn (QueryBuilder $sub) => $sub
                ->select('cai_trail_id')
                ->from('cai_trail_section')
                ->whereIn('section_code', CaiRegionalGroupDashboardMetrics::sectionsOf($prefix)->select('codice_cai')))
            ->toBase()
            ->selectRaw('COALESCE(SUM(length_km), 0) AS km, COUNT(*) AS trails')
            ->first();

        return [(float) ($row->km ?? 0), (int) ($row->trails ?? 0)];
    }

    /**
     * Percorribilità, sentieri senza Sezione e condivisi fra i sentieri presenti, in una query.
     *
     * @return array{practicability: array<string, array{km: float, count: int}>, withoutSectionCount: int, withoutSectionKm: float, sharedCount: int}
     */
    private static function presentSummary(int $regionId): array
    {
        $trails = self::presentTrails($regionId)
            ->toBase()
            ->select(['cai_trails.practicability', 'cai_trails.length_km'])
            ->selectSub(self::sectionsCountSubquery('cai_trails.id'), 'sections_count');

        $rows = DB::query()
            ->fromSub($trails, 'present')
            ->selectRaw('practicability, COUNT(*) AS trails, COALESCE(SUM(length_km), 0) AS km,
                SUM(CASE WHEN sections_count = 0 THEN 1 ELSE 0 END) AS without_section,
                COALESCE(SUM(CASE WHEN sections_count = 0 THEN length_km ELSE 0 END), 0) AS without_section_km,
                SUM(CASE WHEN sections_count > 1 THEN 1 ELSE 0 END) AS shared')
            ->groupBy('practicability')
            ->get();

        $summary = ['practicability' => [], 'withoutSectionCount' => 0, 'withoutSectionKm' => 0.0, 'sharedCount' => 0];

        foreach ($rows as $row) {
            $practicability = CaiTrailPracticability::tryFrom((string) $row->practicability) ?? CaiTrailPracticability::Unknown;
            $current = $summary['practicability'][$practicability->value] ?? ['km' => 0.0, 'count' => 0];
            $summary['practicability'][$practicability->value] = [
                'km' => $current['km'] + (float) $row->km,
                'count' => $current['count'] + (int) $row->trails,
            ];
            $summary['withoutSectionCount'] += (int) $row->without_section;
            $summary['withoutSectionKm'] += (float) $row->without_section_km;
            $summary['sharedCount'] += (int) $row->shared;
        }

        return $summary;
    }

    /** Numero delle Sezioni esistenti legate al sentiero della colonna indicata (senza binding: usabile in `whereRaw`). */
    private static function sectionsCountSubquery(string $trailColumn): QueryBuilder
    {
        return DB::table('cai_trail_section AS counted')
            ->join('cai_sections AS counted_sections', 'counted_sections.codice_cai', '=', 'counted.section_code')
            ->whereColumn('counted.cai_trail_id', $trailColumn)
            ->selectRaw('COUNT(*)');
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, CaiTrailKmStats> id del territorio → km e sentieri presenti per stato
     */
    private static function territoryStats(CaiTrailTerritoryType $type, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $stats = [];

        $rows = DB::table('cai_trail_territory_km')
            ->where('territory_type', $type->value)
            ->whereIn('territory_id', $ids)
            ->get(['territory_id', 'status', 'km', 'present_routes']);

        foreach ($rows as $row) {
            $status = CaiTrailStatus::tryFrom((int) $row->status);

            if ($status === null) {
                continue;
            }

            $id = (int) $row->territory_id;
            $stats[$id] = ($stats[$id] ?? CaiTrailKmStats::empty())->with($status, (float) $row->km, (int) $row->present_routes);
        }

        return $stats;
    }

    /** @return list<CaiRegionalGroupTrailProvinceRow> */
    private static function provinces(int $regionId): array
    {
        $provinces = CaiTrailProvince::query()->where('region_id', $regionId)->orderBy('name')->get(['id', 'name', 'code']);
        $sectors = CaiTrailSector::query()
            ->whereIn('province_id', $provinces->modelKeys())
            ->orderBy('full_code')
            ->orderBy('id')
            ->get(['id', 'province_id', 'code', 'full_code', 'name']);

        $provinceStats = self::territoryStats(CaiTrailTerritoryType::Province, array_map('intval', $provinces->modelKeys()));
        $sectorStats = self::territoryStats(CaiTrailTerritoryType::Sector, array_map('intval', $sectors->modelKeys()));

        $sectorsByProvince = [];

        foreach ($sectors as $sector) {
            $sectorsByProvince[$sector->province_id][] = new CaiRegionalGroupTrailSectorRow(
                id: $sector->id,
                code: $sector->code,
                fullCode: $sector->full_code,
                name: $sector->name,
                stats: $sectorStats[$sector->id] ?? CaiTrailKmStats::empty(),
                hasTrails: isset($sectorStats[$sector->id]),
            );
        }

        return $provinces->map(fn (CaiTrailProvince $province): CaiRegionalGroupTrailProvinceRow => new CaiRegionalGroupTrailProvinceRow(
            id: (int) $province->id,
            name: $province->name,
            code: $province->code,
            stats: $provinceStats[(int) $province->id] ?? CaiTrailKmStats::empty(),
            sectors: $sectorsByProvince[(int) $province->id] ?? [],
        ))->values()->all();
    }

    /** @return list<CaiRegionalGroupTrailSectionRow> */
    private static function sections(string $prefix): array
    {
        $sections = CaiRegionalGroupDashboardMetrics::sectionsOf($prefix)->orderBy('name')->get(['codice_cai', 'name']);
        $codes = $sections->pluck('codice_cai')->map(fn (mixed $code): string => (string) $code)->all();

        $stats = [];

        $rows = DB::table('cai_trail_section')
            ->join('cai_trails', 'cai_trails.id', '=', 'cai_trail_section.cai_trail_id')
            ->whereIn('cai_trail_section.section_code', $codes)
            ->groupBy('cai_trail_section.section_code', 'cai_trails.status')
            ->selectRaw('cai_trail_section.section_code, cai_trails.status, COUNT(*) AS trails, COALESCE(SUM(cai_trails.length_km), 0) AS km')
            ->get();

        foreach ($rows as $row) {
            $status = CaiTrailStatus::tryFrom((int) $row->status);

            if ($status !== null) {
                $code = (string) $row->section_code;
                $stats[$code] = ($stats[$code] ?? CaiTrailKmStats::empty())->with($status, (float) $row->km, (int) $row->trails);
            }
        }

        /** @var array<string, int> $shared */
        $shared = DB::table('cai_trail_section')
            ->whereIn('section_code', $codes)
            ->whereRaw('('.self::sectionsCountSubquery('cai_trail_section.cai_trail_id')->toSql().') > 1')
            ->groupBy('section_code')
            ->selectRaw('section_code, COUNT(*) AS shared')
            ->pluck('shared', 'section_code')
            ->mapWithKeys(fn (mixed $count, mixed $code): array => [(string) $code => (int) $count])
            ->all();

        return $sections->map(fn (CaiSection $section): CaiRegionalGroupTrailSectionRow => new CaiRegionalGroupTrailSectionRow(
            codiceCai: (string) $section->codice_cai,
            name: (string) $section->name,
            stats: $stats[(string) $section->codice_cai] ?? CaiTrailKmStats::empty(),
            sharedCount: $shared[(string) $section->codice_cai] ?? 0,
        ))->values()->all();
    }

    /** @param  Builder<CaiTrail>  $query */
    private static function applyFilters(Builder $query, CaiRegionalGroupTrailFilters $filters): void
    {
        if ($filters->provinceId !== null) {
            self::wherePresentIn($query, 'province_id', $filters->provinceId, sector: false);
        }

        if ($filters->sectorId !== null) {
            self::wherePresentIn($query, 'sector_id', $filters->sectorId, sector: true);
        }

        if ($filters->section === CaiRegionalGroupTrailFilters::WITHOUT_SECTION) {
            $query->whereRaw('('.self::sectionsCountSubquery('cai_trails.id')->toSql().') = 0');
        } elseif ($filters->section !== null) {
            $query->whereIn('cai_trails.id', fn (QueryBuilder $sub) => $sub
                ->select('cai_trail_id')
                ->from('cai_trail_section')
                ->where('section_code', $filters->section));
        }

        if ($filters->status !== null) {
            $query->where('cai_trails.status', $filters->status->value);
        }

        if ($filters->practicability !== null) {
            $query->where('cai_trails.practicability', $filters->practicability->value);
        }

        $search = mb_strtolower(trim($filters->search));

        if ($search !== '') {
            $contains = '%'.$search.'%';
            $query->where(fn (Builder $q) => $q
                ->whereRaw('LOWER(cai_trails.ref) LIKE ?', [$search.'%'])
                ->orWhereRaw('LOWER(cai_trails.name) LIKE ?', [$contains])
                ->orWhereRaw('LOWER(cai_trails.from_place) LIKE ?', [$contains])
                ->orWhereRaw('LOWER(cai_trails.to_place) LIKE ?', [$contains]));
        }
    }

    /** @param  Builder<CaiTrail>  $query */
    private static function wherePresentIn(Builder $query, string $column, int $id, bool $sector): void
    {
        $query->whereIn('cai_trails.id', fn (QueryBuilder $sub) => $sub
            ->select('cai_trail_id')
            ->from('cai_trail_territory')
            ->where($column, $id)
            ->when(! $sector, fn (QueryBuilder $level) => $level->whereNull('sector_id'))
            ->where('percentage', '>=', 50));
    }

    /**
     * Ordinamento in SQL come {@see CaiTrailSort}: numero in ordine naturale sulla parte numerica iniziale ("2" < "2A" <
     * "10"), poi i numeri che iniziano con una lettera in ordine alfabetico ("STI 10" prima di "STI 2": approssimazione
     * di `strnatcasecmp` della 10.6), numero o km mancanti sempre in fondo, a parità per numero.
     *
     * @param  Builder<CaiTrail>  $query
     */
    private static function applySort(Builder $query, CaiRegionalGroupTrailFilters $filters): void
    {
        $direction = $filters->sort->isDescending() ? 'desc' : 'asc';
        $missingRef = "CASE WHEN cai_trails.ref IS NULL OR cai_trails.ref = '' THEN 1 ELSE 0 END";
        $leadingNumber = DB::connection()->getDriverName() === 'pgsql'
            ? "CAST(NULLIF(SUBSTRING(cai_trails.ref FROM '^[0-9]+'), '') AS NUMERIC)"
            : "CASE WHEN cai_trails.ref GLOB '[0-9]*' THEN CAST(cai_trails.ref AS INTEGER) END";

        $byNumber = function (string $direction) use ($query, $leadingNumber): void {
            // Senza parte numerica dopo i numeri (come strnatcasecmp: le cifre vengono prima delle lettere).
            $query->orderByRaw("CASE WHEN {$leadingNumber} IS NULL THEN 1 ELSE 0 END {$direction}")
                ->orderByRaw("{$leadingNumber} {$direction}")
                ->orderByRaw("LOWER(cai_trails.ref) {$direction}");
        };

        match ($filters->sort->column()) {
            'km' => $query->orderByRaw('CASE WHEN cai_trails.length_km IS NULL THEN 1 ELSE 0 END')
                ->orderBy('cai_trails.length_km', $direction),
            'stato' => $query->orderBy('cai_trails.status', $direction),
            default => null,
        };

        $query->orderByRaw($missingRef);
        $byNumber($filters->sort->column() === 'numero' ? $direction : 'asc');
        $query->orderBy('cai_trails.id');
    }

    /**
     * Relazioni di una riga ({@see row()}): Sezioni per nome, provincia e settore della regione.
     *
     * @return array<string, \Closure>
     */
    private static function rowRelations(int $regionId): array
    {
        return [
            'sections' => fn ($sections) => $sections->select(['cai_sections.codice_cai', 'cai_sections.name'])
                ->orderBy('cai_sections.name'),
            'territories' => fn ($territories) => $territories->where('region_id', $regionId)
                ->whereNotNull('province_id')
                ->with(['province:id,region_id,name,code', 'sector:id,province_id,code,full_code,name']),
        ];
    }

    private static function row(CaiTrail $trail): CaiRegionalGroupTrailRow
    {
        /** @var CaiTrailTerritory|null $sectorLink */
        $sectorLink = $trail->territories->whereNotNull('sector_id')->sortByDesc('percentage')->first();
        /** @var CaiTrailTerritory|null $provinceLink */
        $provinceLink = $trail->territories->whereNull('sector_id')->sortByDesc('percentage')->first();

        $sector = $sectorLink?->sector;
        $province = $sector !== null ? $sectorLink->province : $provinceLink?->province;

        $trail->unsetRelation('territories');

        return new CaiRegionalGroupTrailRow($trail, $province, $sector);
    }
}
