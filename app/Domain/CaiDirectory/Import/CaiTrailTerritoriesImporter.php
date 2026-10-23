<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import;

use App\Domain\CaiDirectory\Enums\CaiTrailStatus;
use App\Domain\CaiDirectory\Enums\CaiTrailTerritoryType;
use App\Domain\Identity\Enums\Region;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Importa i territori dei sentieri osm2cai del datapack (`snap_osm2cai_{regions,provinces,sectors,territory_km,
 * trail_territory}`, scritte da `bin/update-osm2cai-datapack`, US-1061) in `cai_trail_{regions,provinces,sectors,
 * territory_km,territory}` (US-1062), riga `snapshot_territori_sentieri`. Va eseguito DOPO {@see CaiTrailsImporter}: i
 * legami sentiero–territorio puntano a `cai_trails`.
 *
 * Il contenuto si **sostituisce** in blocco: le cinque tabelle sono statistiche calcolate insieme dallo script, senza
 * identità stabile riga per riga. Idempotente: se nessuna riga normalizzata cambia non si scrive nulla; altrimenti si
 * svuotano e si riscrivono tutte, contando come create/eliminate solo le righe davvero aggiunte/sparite. Righe orfane
 * (genitore assente, sentiero non importato, stato fuori da 1–4) saltate con avviso. Senza una delle cinque tabelle il
 * passo è saltato con un avviso (mai svuotare i territori per un datapack che non li contiene).
 */
final class CaiTrailTerritoriesImporter
{
    public const RESULT_KEY = 'snapshot_territori_sentieri';

    private const CHUNK = 500;

    /** Tabelle di destinazione nell'ordine di inserimento (genitori prima): si svuotano al contrario. */
    private const TABLES = ['cai_trail_regions', 'cai_trail_provinces', 'cai_trail_sectors', 'cai_trail_territory_km', 'cai_trail_territory'];

    /**
     * @return array<string, CaiImportTableResult>
     */
    public function import(Connection $datapack, bool $dryRun): array
    {
        $schema = $datapack->getSchemaBuilder();
        $sources = [
            CaiOsm2caiExtractWriter::REGIONS_TABLE,
            CaiOsm2caiExtractWriter::PROVINCES_TABLE,
            CaiOsm2caiExtractWriter::SECTORS_TABLE,
            CaiOsm2caiExtractWriter::TERRITORY_KM_TABLE,
            CaiOsm2caiExtractWriter::TRAIL_TERRITORY_TABLE,
        ];
        $missing = array_values(array_filter($sources, fn (string $table): bool => ! $schema->hasTable($table)));

        if ($missing !== []) {
            return [self::RESULT_KEY => new CaiImportTableResult(
                warnings: ['Tabelle '.implode('/', $missing).' assenti nel datapack: territori dei sentieri non aggiornati (bin/update-osm2cai-datapack)'],
            )];
        }

        $run = fn (): CaiImportTableResult => $this->run($datapack, $dryRun);

        return [self::RESULT_KEY => $dryRun ? $run() : DB::transaction($run)];
    }

    private function run(Connection $datapack, bool $dryRun): CaiImportTableResult
    {
        $orphans = [];
        $source = $this->sourceRows($datapack, $dryRun, $orphans);

        $read = 0;
        $added = 0;
        $removed = 0;

        foreach (self::TABLES as $table) {
            $read += count($source[$table]) + ($orphans[$table] ?? 0);
            $current = $this->currentKeys($table);
            $added += count(array_diff_key($source[$table], $current));
            $removed += count(array_diff_key($current, $source[$table]));
        }

        if (! $dryRun && ($added > 0 || $removed > 0)) {
            foreach (array_reverse(self::TABLES) as $table) {
                DB::table($table)->delete();
            }

            foreach (self::TABLES as $table) {
                foreach (array_chunk(array_values($source[$table]), self::CHUNK) as $chunk) {
                    DB::table($table)->insert($chunk);
                }
            }
        }

        $warnings = [];
        $labels = [
            'cai_trail_provinces' => 'province senza regione',
            'cai_trail_sectors' => 'settori senza provincia',
            'cai_trail_territory_km' => 'statistiche con territorio sconosciuto o stato fuori da 1–4',
            'cai_trail_territory' => 'legami sentiero-territorio senza sentiero importato o con territorio sconosciuto',
        ];

        foreach ($labels as $table => $label) {
            if (($orphans[$table] ?? 0) > 0) {
                $warnings[] = "{$orphans[$table]} {$label} saltati";
            }
        }

        if (($orphans['cai_trail_regions'] ?? 0) > 0) {
            $warnings[] = "{$orphans['cai_trail_regions']} regioni con region_key sconosciuta saltate";
        }

        return new CaiImportTableResult(
            read: $read,
            created: $added,
            skipped: $read - $added,
            warnings: $warnings,
            deleted: $removed,
        );
    }

    /**
     * Righe normalizzate da scrivere, per tabella di destinazione, con chiave = contenuto della riga (geometria esclusa:
     * vedi {@see key()}).
     *
     * @param  array<string, int>  $orphans  tabella → righe saltate
     * @return array<string, array<string, array<string, int|string|null>>>
     */
    private function sourceRows(Connection $datapack, bool $dryRun, array &$orphans): array
    {
        $rows = array_fill_keys(self::TABLES, []);
        $skip = function (string $table) use (&$orphans): void {
            $orphans[$table] = ($orphans[$table] ?? 0) + 1;
        };

        foreach ($datapack->table(CaiOsm2caiExtractWriter::REGIONS_TABLE)->orderBy('id')->get() as $row) {
            if (Region::tryFrom((string) $row->region_key) === null) {
                $skip('cai_trail_regions');

                continue;
            }

            $this->put($rows, 'cai_trail_regions', ['id' => (int) $row->id, 'name' => trim((string) $row->name), 'region_key' => (string) $row->region_key]);
        }

        $regionIds = array_flip(array_column($rows['cai_trail_regions'], 'id'));

        foreach ($datapack->table(CaiOsm2caiExtractWriter::PROVINCES_TABLE)->orderBy('id')->get() as $row) {
            if (! isset($regionIds[(int) $row->region_id])) {
                $skip('cai_trail_provinces');

                continue;
            }

            $this->put($rows, 'cai_trail_provinces', [
                'id' => (int) $row->id,
                'region_id' => (int) $row->region_id,
                'name' => trim((string) $row->name),
                'code' => $this->text($row->code),
            ]);
        }

        $provinceIds = array_flip(array_column($rows['cai_trail_provinces'], 'id'));

        foreach ($datapack->table(CaiOsm2caiExtractWriter::SECTORS_TABLE)->orderBy('id')->cursor() as $row) {
            if (! isset($provinceIds[(int) $row->province_id])) {
                $skip('cai_trail_sectors');

                continue;
            }

            $this->put($rows, 'cai_trail_sectors', [
                'id' => (int) $row->id,
                'province_id' => (int) $row->province_id,
                'code' => $this->text($row->code),
                'full_code' => $this->text($row->full_code),
                'name' => $this->text($row->name),
                'geometry' => $this->text($row->geometry_geojson),
            ]);
        }

        $sectorIds = array_flip(array_column($rows['cai_trail_sectors'], 'id'));
        $territoryIds = [
            CaiTrailTerritoryType::Region->value => $regionIds,
            CaiTrailTerritoryType::Province->value => $provinceIds,
            CaiTrailTerritoryType::Sector->value => $sectorIds,
        ];

        foreach ($datapack->table(CaiOsm2caiExtractWriter::TERRITORY_KM_TABLE)->orderBy('territory_type')->orderBy('territory_id')->orderBy('status')->cursor() as $row) {
            $type = CaiTrailTerritoryType::tryFrom((string) $row->territory_type);

            if ($type === null || ! isset($territoryIds[$type->value][(int) $row->territory_id]) || CaiTrailStatus::tryFrom((int) $row->status) === null) {
                $skip('cai_trail_territory_km');

                continue;
            }

            $this->put($rows, 'cai_trail_territory_km', [
                'territory_type' => $type->value,
                'territory_id' => (int) $row->territory_id,
                'status' => (int) $row->status,
                'km' => number_format((float) $row->km, 3, '.', ''),
                'present_routes' => (int) $row->present_routes,
            ]);
        }

        // In dry-run i sentieri nuovi non sono ancora in `cai_trails`: basta che siano nel datapack.
        $trailIds = array_flip(($dryRun
            ? $datapack->table(CaiOsm2caiExtractWriter::TRAILS_TABLE)->pluck('id')
            : DB::table('cai_trails')->pluck('id'))->map(fn ($id): int => (int) $id)->all());
        $nullableId = fn (mixed $value): ?int => $value === null || $value === '' ? null : (int) $value;

        foreach ($datapack->table(CaiOsm2caiExtractWriter::TRAIL_TERRITORY_TABLE)->orderBy('trail_id')->cursor() as $row) {
            $regionId = $nullableId($row->region_id);
            $provinceId = $nullableId($row->province_id);
            $sectorId = $nullableId($row->sector_id);

            if (! isset($trailIds[(int) $row->trail_id])
                || $regionId === null || ! isset($regionIds[$regionId])
                || ($provinceId !== null && ! isset($provinceIds[$provinceId]))
                || ($sectorId !== null && ! isset($sectorIds[$sectorId]))) {
                $skip('cai_trail_territory');

                continue;
            }

            $this->put($rows, 'cai_trail_territory', [
                'cai_trail_id' => (int) $row->trail_id,
                'region_id' => $regionId,
                'province_id' => $provinceId,
                'sector_id' => $sectorId,
                'percentage' => number_format((float) $row->percentage, 2, '.', ''),
            ]);
        }

        return $rows;
    }

    /**
     * @param  array<string, array<string, array<string, int|string|null>>>  $rows
     * @param  array<string, int|string|null>  $row
     */
    private function put(array &$rows, string $table, array $row): void
    {
        $rows[$table][$this->key($row)] = $row;
    }

    /**
     * Chiave di confronto: tutte le colonne, con la geometria ridotta a un hash (stringa della fonte salvata identica,
     * colonna `json` e non `jsonb`).
     *
     * @param  array<string, int|string|null>  $row
     */
    private function key(array $row): string
    {
        if (array_key_exists('geometry', $row)) {
            $row['geometry'] = $row['geometry'] === null ? null : md5((string) $row['geometry']);
        }

        return implode('|', array_map(fn (mixed $value): string => $value === null ? '∅' : (string) $value, $row));
    }

    /**
     * Chiavi delle righe in tabella, normalizzate come quelle della fonte (Postgres restituisce i decimali come stringa).
     *
     * @return array<string, true>
     */
    private function currentKeys(string $table): array
    {
        $keys = [];

        foreach (DB::table($table)->cursor() as $row) {
            $keys[$this->key($this->normalizeCurrent($table, $row))] = true;
        }

        return $keys;
    }

    /**
     * @return array<string, int|string|null>
     */
    private function normalizeCurrent(string $table, stdClass $row): array
    {
        $id = fn (mixed $value): ?int => $value === null ? null : (int) $value;

        return match ($table) {
            'cai_trail_regions' => ['id' => (int) $row->id, 'name' => (string) $row->name, 'region_key' => (string) $row->region_key],
            'cai_trail_provinces' => ['id' => (int) $row->id, 'region_id' => (int) $row->region_id, 'name' => (string) $row->name, 'code' => $row->code],
            'cai_trail_sectors' => [
                'id' => (int) $row->id,
                'province_id' => (int) $row->province_id,
                'code' => $row->code,
                'full_code' => $row->full_code,
                'name' => $row->name,
                'geometry' => $row->geometry,
            ],
            'cai_trail_territory_km' => [
                'territory_type' => (string) $row->territory_type,
                'territory_id' => (int) $row->territory_id,
                'status' => (int) $row->status,
                'km' => number_format((float) $row->km, 3, '.', ''),
                'present_routes' => (int) $row->present_routes,
            ],
            default => [
                'cai_trail_id' => (int) $row->cai_trail_id,
                'region_id' => (int) $row->region_id,
                'province_id' => $id($row->province_id),
                'sector_id' => $id($row->sector_id),
                'percentage' => number_format((float) $row->percentage, 2, '.', ''),
            ],
        };
    }

    private function text(mixed $value): ?string
    {
        return $value === null || trim((string) $value) === '' ? null : trim((string) $value);
    }
}
