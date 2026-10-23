<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import;

use App\Domain\CaiDirectory\Support\CaiOsm2caiRegionName;
use App\Domain\Identity\Enums\Region;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Scrive nel datapack le tabelle `snap_osm2cai_*` a partire dai CSV estratti dal dump osm2cai (US-1051, territori
 * US-1061) da `bin/update-osm2cai-datapack` con `database/osm2cai/extract.sql`. Una sola transazione sul file SQLite:
 * DROP+CREATE+INSERT delle sole tabelle `snap_osm2cai_*`; in caso di errore (CSV mancante, intestazione diversa, due
 * regioni della fonte abbinate alla stessa regione italiana) restano le tabelle precedenti e le altre tabelle del
 * datapack non vengono mai toccate.
 *
 * Territori: `regions.csv` contiene tutte le regioni della fonte, anche estere; si tengono solo quelle abbinate per nome
 * all'enum {@see Region} ({@see CaiOsm2caiRegionName}, colonna `region_key`) e si scartano province, settori,
 * statistiche e legami delle altre.
 */
final class CaiOsm2caiExtractWriter
{
    public const TRAILS_TABLE = 'snap_osm2cai_trails';

    public const TRAIL_SECTIONS_TABLE = 'snap_osm2cai_trail_sections';

    public const REGIONS_TABLE = 'snap_osm2cai_regions';

    public const PROVINCES_TABLE = 'snap_osm2cai_provinces';

    public const SECTORS_TABLE = 'snap_osm2cai_sectors';

    public const TERRITORY_KM_TABLE = 'snap_osm2cai_territory_km';

    public const TRAIL_TERRITORY_TABLE = 'snap_osm2cai_trail_territory';

    /**
     * Colonne di `snap_osm2cai_trails` → definizione SQLite, nell'ordine dell'intestazione di `trails.csv`.
     *
     * @var array<string, string>
     */
    public const TRAIL_COLUMNS = [
        'id' => 'INTEGER NOT NULL',
        'osmid' => 'INTEGER',
        'ref' => 'TEXT',
        'name' => 'TEXT',
        'from_place' => 'TEXT',
        'to_place' => 'TEXT',
        'cai_scale' => 'TEXT',
        'length_km' => 'REAL',
        'ascent' => 'INTEGER',
        'descent' => 'INTEGER',
        'status' => 'INTEGER NOT NULL',
        'issues_status' => 'TEXT',
        'issues_last_update' => 'TEXT',
        'validation_date' => 'TEXT',
        'geometry_geojson' => 'TEXT',
    ];

    /**
     * Colonne di `snap_osm2cai_trail_sections` → definizione SQLite, nell'ordine dell'intestazione di `trail_sections.csv`.
     *
     * @var array<string, string>
     */
    public const TRAIL_SECTION_COLUMNS = [
        'trail_id' => 'INTEGER NOT NULL',
        'section_code' => 'TEXT NOT NULL',
    ];

    /**
     * Colonne di `regions.csv` (tutte le regioni della fonte): `snap_osm2cai_regions` aggiunge `region_key`.
     *
     * @var array<string, string>
     */
    public const REGION_COLUMNS = [
        'id' => 'INTEGER NOT NULL',
        'name' => 'TEXT NOT NULL',
    ];

    /**
     * @var array<string, string>
     */
    public const PROVINCE_COLUMNS = [
        'id' => 'INTEGER NOT NULL',
        'region_id' => 'INTEGER NOT NULL',
        'name' => 'TEXT NOT NULL',
        'code' => 'TEXT',
    ];

    /**
     * @var array<string, string>
     */
    public const SECTOR_COLUMNS = [
        'id' => 'INTEGER NOT NULL',
        'province_id' => 'INTEGER NOT NULL',
        'code' => 'TEXT',
        'full_code' => 'TEXT',
        'name' => 'TEXT',
        'geometry_geojson' => 'TEXT',
    ];

    /**
     * `territory_type` = `region` / `province` / `sector`; `km` intersecati e sentieri presenti (≥ 50%) per stato 1–4.
     *
     * @var array<string, string>
     */
    public const TERRITORY_KM_COLUMNS = [
        'territory_type' => 'TEXT NOT NULL',
        'territory_id' => 'INTEGER NOT NULL',
        'status' => 'INTEGER NOT NULL',
        'km' => 'REAL NOT NULL',
        'present_routes' => 'INTEGER NOT NULL',
    ];

    /**
     * Legame sentiero–territorio: il livello è il più profondo id valorizzato (regione, provincia, settore).
     *
     * @var array<string, string>
     */
    public const TRAIL_TERRITORY_COLUMNS = [
        'trail_id' => 'INTEGER NOT NULL',
        'region_id' => 'INTEGER',
        'province_id' => 'INTEGER',
        'sector_id' => 'INTEGER',
        'percentage' => 'REAL NOT NULL',
    ];

    /**
     * @throws RuntimeException se un CSV manca o ha un'intestazione diversa da quella attesa
     */
    public function write(PDO $datapack, string $csvDirectory): CaiOsm2caiExtractSummary
    {
        $datapack->beginTransaction();

        try {
            foreach ([self::TRAILS_TABLE, self::TRAIL_SECTIONS_TABLE, self::REGIONS_TABLE, self::PROVINCES_TABLE, self::SECTORS_TABLE, self::TERRITORY_KM_TABLE, self::TRAIL_TERRITORY_TABLE] as $table) {
                $datapack->exec('DROP TABLE IF EXISTS '.$table);
            }

            $datapack->exec('CREATE TABLE '.self::TRAILS_TABLE.' ('.$this->columnDefinitions(self::TRAIL_COLUMNS).', PRIMARY KEY (id))');
            $datapack->exec('CREATE TABLE '.self::TRAIL_SECTIONS_TABLE.' ('.$this->columnDefinitions(self::TRAIL_SECTION_COLUMNS).', PRIMARY KEY (trail_id, section_code))');
            $datapack->exec('CREATE TABLE '.self::REGIONS_TABLE.' ('.$this->columnDefinitions(self::REGION_COLUMNS).', region_key TEXT NOT NULL UNIQUE, PRIMARY KEY (id))');
            $datapack->exec('CREATE TABLE '.self::PROVINCES_TABLE.' ('.$this->columnDefinitions(self::PROVINCE_COLUMNS).', PRIMARY KEY (id))');
            $datapack->exec('CREATE TABLE '.self::SECTORS_TABLE.' ('.$this->columnDefinitions(self::SECTOR_COLUMNS).', PRIMARY KEY (id))');
            $datapack->exec('CREATE TABLE '.self::TERRITORY_KM_TABLE.' ('.$this->columnDefinitions(self::TERRITORY_KM_COLUMNS).', PRIMARY KEY (territory_type, territory_id, status))');
            $datapack->exec('CREATE TABLE '.self::TRAIL_TERRITORY_TABLE.' ('.$this->columnDefinitions(self::TRAIL_TERRITORY_COLUMNS).')');
            $datapack->exec('CREATE INDEX '.self::TRAIL_TERRITORY_TABLE.'_trail_id_index ON '.self::TRAIL_TERRITORY_TABLE.' (trail_id)');

            $trails = $this->copy($datapack, $csvDirectory.'/trails.csv', self::TRAILS_TABLE, self::TRAIL_COLUMNS);
            $this->copy($datapack, $csvDirectory.'/trail_sections.csv', self::TRAIL_SECTIONS_TABLE, self::TRAIL_SECTION_COLUMNS);
            $foreignRegions = $this->copyRegions($datapack, $csvDirectory.'/regions.csv');
            $this->copy($datapack, $csvDirectory.'/provinces.csv', self::PROVINCES_TABLE, self::PROVINCE_COLUMNS);
            $this->copy($datapack, $csvDirectory.'/sectors.csv', self::SECTORS_TABLE, self::SECTOR_COLUMNS);
            $this->copy($datapack, $csvDirectory.'/territory_km.csv', self::TERRITORY_KM_TABLE, self::TERRITORY_KM_COLUMNS);
            $this->copy($datapack, $csvDirectory.'/trail_territory.csv', self::TRAIL_TERRITORY_TABLE, self::TRAIL_TERRITORY_COLUMNS);
            $this->dropForeignTerritories($datapack);

            $datapack->commit();
        } catch (Throwable $e) {
            $datapack->rollBack();

            throw $e;
        }

        return new CaiOsm2caiExtractSummary(
            trails: $trails,
            linkedTrails: (int) $datapack->query(
                'SELECT COUNT(DISTINCT trail_id) FROM '.self::TRAIL_SECTIONS_TABLE.' WHERE section_code IN (SELECT codice_cai FROM sezioni_cai)'
            )->fetchColumn(),
            unknownSectionCodes: $this->unknownSectionCodes($datapack),
            regions: $this->regionTotals($datapack),
            foreignRegions: $foreignRegions,
            provinces: (int) $datapack->query('SELECT COUNT(*) FROM '.self::PROVINCES_TABLE)->fetchColumn(),
            sectors: (int) $datapack->query('SELECT COUNT(*) FROM '.self::SECTORS_TABLE)->fetchColumn(),
            trailTerritories: (int) $datapack->query('SELECT COUNT(*) FROM '.self::TRAIL_TERRITORY_TABLE)->fetchColumn(),
        );
    }

    /**
     * @param  array<string, string>  $columns
     */
    private function columnDefinitions(array $columns): string
    {
        return implode(', ', array_map(fn (string $column, string $type): string => $column.' '.$type, array_keys($columns), $columns));
    }

    /**
     * Copia un CSV (intestazione obbligatoria, uguale a `$columns`) nella tabella: celle vuote → NULL. `$map` può
     * aggiungere colonne alla riga tipizzata (nell'ordine di `$extraColumns`) o scartarla restituendo `null`.
     *
     * @param  array<string, string>  $columns
     * @param  list<string>  $extraColumns
     * @param  (callable(list<int|float|string|null>): (list<int|float|string|null>|null))|null  $map
     */
    private function copy(PDO $datapack, string $path, string $table, array $columns, array $extraColumns = [], ?callable $map = null): int
    {
        $handle = is_file($path) ? fopen($path, 'rb') : false;

        if ($handle === false) {
            throw new RuntimeException("CSV non trovato: {$path}");
        }

        try {
            $header = fgetcsv($handle, escape: '');

            if ($header !== array_keys($columns)) {
                throw new RuntimeException("Intestazione inattesa in {$path}: attese le colonne ".implode(',', array_keys($columns)));
            }

            $names = [...array_keys($columns), ...$extraColumns];
            $insert = $datapack->prepare('INSERT INTO '.$table.' ('.implode(', ', $names).') VALUES ('.implode(', ', array_fill(0, count($names), '?')).')');
            $types = array_values($columns);
            $rows = 0;

            while (($row = fgetcsv($handle, escape: '')) !== false) {
                if ($row === [null]) {
                    continue;
                }

                $values = array_map(
                    fn (?string $value, string $type): int|float|string|null => match (true) {
                        $value === null || $value === '' => null,
                        str_starts_with($type, 'INTEGER') => (int) $value,
                        str_starts_with($type, 'REAL') => (float) $value,
                        default => $value,
                    },
                    $row,
                    $types,
                );
                $values = $map === null ? $values : $map($values);

                if ($values === null) {
                    continue;
                }

                $insert->execute($values);
                $rows++;
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    /**
     * Copia `regions.csv` tenendo solo le regioni abbinate all'enum {@see Region}.
     *
     * @return list<string> nomi delle regioni della fonte scartate (estere), in ordine di CSV
     *
     * @throws RuntimeException se due regioni della fonte si abbinano alla stessa regione italiana
     */
    private function copyRegions(PDO $datapack, string $path): array
    {
        $foreign = [];
        $matched = [];

        $this->copy($datapack, $path, self::REGIONS_TABLE, self::REGION_COLUMNS, ['region_key'], function (array $row) use (&$foreign, &$matched): ?array {
            $name = (string) $row[1];
            $region = CaiOsm2caiRegionName::toRegion($name);

            if ($region === null) {
                $foreign[] = $name;

                return null;
            }

            if (isset($matched[$region->value])) {
                throw new RuntimeException("Le regioni \"{$matched[$region->value]}\" e \"{$name}\" della fonte corrispondono entrambe a {$region->label()}");
            }

            $matched[$region->value] = $name;

            return [...$row, $region->value];
        });

        return $foreign;
    }

    /**
     * Scarta province, settori, statistiche e legami che non appartengono a una regione italiana tenuta.
     */
    private function dropForeignTerritories(PDO $datapack): void
    {
        $regions = 'SELECT id FROM '.self::REGIONS_TABLE;
        $provinces = 'SELECT id FROM '.self::PROVINCES_TABLE;
        $sectors = 'SELECT id FROM '.self::SECTORS_TABLE;

        $datapack->exec('DELETE FROM '.self::PROVINCES_TABLE." WHERE region_id NOT IN ({$regions})");
        $datapack->exec('DELETE FROM '.self::SECTORS_TABLE." WHERE province_id NOT IN ({$provinces})");
        $datapack->exec('DELETE FROM '.self::TERRITORY_KM_TABLE
            ." WHERE (territory_type = 'region' AND territory_id NOT IN ({$regions}))"
            ." OR (territory_type = 'province' AND territory_id NOT IN ({$provinces}))"
            ." OR (territory_type = 'sector' AND territory_id NOT IN ({$sectors}))"
            ." OR territory_type NOT IN ('region', 'province', 'sector')");
        $datapack->exec('DELETE FROM '.self::TRAIL_TERRITORY_TABLE
            ." WHERE region_id IS NULL OR region_id NOT IN ({$regions})"
            ." OR (province_id IS NOT NULL AND province_id NOT IN ({$provinces}))"
            ." OR (sector_id IS NOT NULL AND sector_id NOT IN ({$sectors}))");
    }

    /**
     * Km nel territorio e sentieri presenti di ciascuna regione tenuta (anche a zero), in ordine di nome della regione.
     *
     * @return list<array{region: Region, km: float, presentRoutes: int}>
     */
    private function regionTotals(PDO $datapack): array
    {
        $rows = $datapack->query(
            'SELECT r.region_key, COALESCE(SUM(t.km), 0) AS km, COALESCE(SUM(t.present_routes), 0) AS present_routes'
            .' FROM '.self::REGIONS_TABLE.' r'
            .' LEFT JOIN '.self::TERRITORY_KM_TABLE." t ON t.territory_type = 'region' AND t.territory_id = r.id"
            .' GROUP BY r.region_key'
        )->fetchAll(PDO::FETCH_ASSOC);

        $totals = array_map(fn (array $row): array => [
            'region' => Region::from((string) $row['region_key']),
            'km' => (float) $row['km'],
            'presentRoutes' => (int) $row['present_routes'],
        ], $rows);

        usort($totals, fn (array $a, array $b): int => strcmp($a['region']->label(), $b['region']->label()));

        return $totals;
    }

    /**
     * Codici di Sezione dei sentieri assenti da `sezioni_cai` del datapack → numero di sentieri, in ordine di codice.
     *
     * @return array<string, int>
     */
    private function unknownSectionCodes(PDO $datapack): array
    {
        $rows = $datapack->query(
            'SELECT section_code, COUNT(*) FROM '.self::TRAIL_SECTIONS_TABLE
            .' WHERE section_code NOT IN (SELECT codice_cai FROM sezioni_cai WHERE codice_cai IS NOT NULL)'
            .' GROUP BY section_code ORDER BY section_code'
        )->fetchAll(PDO::FETCH_KEY_PAIR);

        return array_map('intval', $rows);
    }
}
