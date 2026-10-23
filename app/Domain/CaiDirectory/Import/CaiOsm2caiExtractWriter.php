<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import;

use PDO;
use RuntimeException;
use Throwable;

/**
 * Scrive nel datapack le tabelle `snap_osm2cai_*` a partire dai CSV estratti dal dump osm2cai (US-1051) da
 * `bin/update-osm2cai-datapack` con `database/osm2cai/extract.sql`. Una sola transazione sul file SQLite: DROP+CREATE+
 * INSERT delle sole tabelle `snap_osm2cai_*`; in caso di errore (CSV mancante, intestazione diversa) restano le tabelle
 * precedenti e le altre tabelle del datapack non vengono mai toccate.
 */
final class CaiOsm2caiExtractWriter
{
    public const TRAILS_TABLE = 'snap_osm2cai_trails';

    public const TRAIL_SECTIONS_TABLE = 'snap_osm2cai_trail_sections';

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
     * @throws RuntimeException se un CSV manca o ha un'intestazione diversa da quella attesa
     */
    public function write(PDO $datapack, string $csvDirectory): CaiOsm2caiExtractSummary
    {
        $datapack->beginTransaction();

        try {
            $datapack->exec('DROP TABLE IF EXISTS '.self::TRAILS_TABLE);
            $datapack->exec('DROP TABLE IF EXISTS '.self::TRAIL_SECTIONS_TABLE);
            $datapack->exec('CREATE TABLE '.self::TRAILS_TABLE.' ('.$this->columnDefinitions(self::TRAIL_COLUMNS).', PRIMARY KEY (id))');
            $datapack->exec('CREATE TABLE '.self::TRAIL_SECTIONS_TABLE.' ('.$this->columnDefinitions(self::TRAIL_SECTION_COLUMNS).', PRIMARY KEY (trail_id, section_code))');

            $trails = $this->copy($datapack, $csvDirectory.'/trails.csv', self::TRAILS_TABLE, self::TRAIL_COLUMNS);
            $this->copy($datapack, $csvDirectory.'/trail_sections.csv', self::TRAIL_SECTIONS_TABLE, self::TRAIL_SECTION_COLUMNS);

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
     * Copia un CSV (intestazione obbligatoria, uguale a `$columns`) nella tabella: celle vuote → NULL.
     *
     * @param  array<string, string>  $columns
     */
    private function copy(PDO $datapack, string $path, string $table, array $columns): int
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

            $insert = $datapack->prepare('INSERT INTO '.$table.' ('.implode(', ', array_keys($columns)).') VALUES ('.implode(', ', array_fill(0, count($columns), '?')).')');
            $types = array_values($columns);
            $rows = 0;

            while (($row = fgetcsv($handle, escape: '')) !== false) {
                if ($row === [null]) {
                    continue;
                }

                $insert->execute(array_map(
                    fn (?string $value, string $type): int|float|string|null => match (true) {
                        $value === null || $value === '' => null,
                        str_starts_with($type, 'INTEGER') => (int) $value,
                        str_starts_with($type, 'REAL') => (float) $value,
                        default => $value,
                    },
                    $row,
                    $types,
                ));
                $rows++;
            }

            return $rows;
        } finally {
            fclose($handle);
        }
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
