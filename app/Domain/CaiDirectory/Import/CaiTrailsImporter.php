<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import;

use App\Domain\CaiDirectory\Enums\CaiTrailPracticability;
use App\Domain\CaiDirectory\Enums\CaiTrailStatus;
use App\Domain\CaiDirectory\Models\CaiTrail;
use App\Domain\CaiDirectory\Support\CaiGeoJsonBounds;
use Illuminate\Database\Connection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Importa i sentieri osm2cai del datapack (`snap_osm2cai_trails` / `snap_osm2cai_trail_sections`, scritte da
 * `bin/update-osm2cai-datapack`) in `cai_trails` / `cai_trail_section` (US-1052). La fonte è di verità: crea, aggiorna ed
 * **elimina** i sentieri e i legami con le Sezioni non più presenti. Idempotente (righe invariate non riscritte);
 * `--dry-run` conta senza scrivere. Senza le due tabelle il passo è saltato con un avviso (mai cancellare i sentieri per
 * un datapack che non li contiene).
 *
 * Scrive con il query builder, a blocchi: ~26.000 sentieri con ~108 MB di GeoJSON. La geometria si salva come la stringa
 * della fonte e si confronta così (colonna `json`, non `jsonb`: il testo resta identico); la si decodifica solo per il
 * riquadro `min_lat`…`max_lng` (US-1064, mappa del GR per area visibile).
 */
final class CaiTrailsImporter
{
    private const CHUNK = 500;

    /** Colonne intere di `cai_trails` (normalizzate in {@see candidate()} e confrontate come interi). */
    private const INTEGER_COLUMNS = ['id', 'osmid', 'ascent', 'descent', 'status'];

    private const DATE_COLUMNS = ['issues_last_update', 'validation_date'];

    /** Riquadro della geometria, 6 decimali (US-1064). */
    private const BOUNDS_COLUMNS = ['min_lat', 'min_lng', 'max_lat', 'max_lng'];

    /**
     * @return array<string, CaiImportTableResult>
     */
    public function import(Connection $datapack, bool $dryRun): array
    {
        $schema = $datapack->getSchemaBuilder();

        if (! $schema->hasTable(CaiOsm2caiExtractWriter::TRAILS_TABLE) || ! $schema->hasTable(CaiOsm2caiExtractWriter::TRAIL_SECTIONS_TABLE)) {
            return ['snapshot_sentieri' => new CaiImportTableResult(
                warnings: ['Tabelle '.CaiOsm2caiExtractWriter::TRAILS_TABLE.'/'.CaiOsm2caiExtractWriter::TRAIL_SECTIONS_TABLE
                    .' assenti nel datapack: sentieri non aggiornati (bin/update-osm2cai-datapack)'],
            )];
        }

        $run = fn (): array => $this->run($datapack, $dryRun);

        return $dryRun ? $run() : DB::transaction($run);
    }

    /**
     * @return array<string, CaiImportTableResult>
     */
    private function run(Connection $datapack, bool $dryRun): array
    {
        [$trails, $importable, $stale] = $this->importTrails($datapack, $dryRun);
        // Legami prima dell'eliminazione: quelli dei sentieri spariti contano fra gli eliminati.
        $sections = $this->importTrailSections($datapack, $importable, $dryRun);

        if (! $dryRun) {
            foreach (array_chunk($stale, self::CHUNK) as $ids) {
                DB::table('cai_trails')->whereIn('id', $ids)->delete();
            }
        }

        return ['snapshot_sentieri' => $trails, 'snapshot_sentieri_sezioni' => $sections];
    }

    /**
     * @return array{CaiImportTableResult, array<int, true>, list<int>} esito, id dei sentieri in `cai_trails` dopo creazioni e
     *                                                                  aggiornamenti, id dei sentieri da eliminare (lo fa {@see run()})
     */
    private function importTrails(Connection $datapack, bool $dryRun): array
    {
        $read = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $invalidStatus = 0;
        $seen = [];
        $importable = [];
        $now = Carbon::now();

        $datapack->table(CaiOsm2caiExtractWriter::TRAILS_TABLE)->orderBy('id')->chunk(self::CHUNK, function ($rows) use (&$read, &$created, &$updated, &$skipped, &$invalidStatus, &$seen, &$importable, $now, $dryRun): void {
            $existing = DB::table('cai_trails')->whereIn('id', $rows->pluck('id')->map(fn ($id): int => (int) $id)->all())->get()->keyBy('id');
            $inserts = [];

            foreach ($rows as $row) {
                $read++;
                $id = (int) $row->id;
                // Anche una riga scartata protegge il sentiero esistente dall'eliminazione.
                $seen[$id] = true;

                if (CaiTrailStatus::tryFrom((int) $row->status) === null) {
                    $invalidStatus++;
                    $skipped++;

                    continue;
                }

                $candidate = $this->candidate($row);
                $current = $existing->get($id);

                if ($current === null) {
                    $inserts[] = [...$candidate, 'created_at' => $now, 'updated_at' => $now];
                    $created++;

                    continue;
                }

                $importable[$id] = true;

                if ($this->differs($current, $candidate)) {
                    if (! $dryRun) {
                        DB::table('cai_trails')->where('id', $id)->update([...$candidate, 'updated_at' => $now]);
                    }
                    $updated++;

                    continue;
                }

                $skipped++;
            }

            if (! $dryRun && $inserts !== []) {
                DB::table('cai_trails')->insert($inserts);

                foreach ($inserts as $insert) {
                    $importable[$insert['id']] = true;
                }
            }
        });

        $stale = CaiTrail::query()->pluck('id')->map(fn ($id): int => (int) $id)->reject(fn (int $id): bool => isset($seen[$id]))->values()->all();

        $result = new CaiImportTableResult(
            read: $read,
            created: $created,
            updated: $updated,
            skipped: $skipped,
            warnings: $invalidStatus > 0 ? ["{$invalidStatus} sentieri con stato fuori da 1–4 saltati"] : [],
            deleted: count($stale),
        );

        return [$result, $importable, $stale];
    }

    /**
     * Legami sentiero ↔ codice di Sezione: si aggiungono i nuovi e si tolgono quelli spariti dalla fonte. I codici
     * osm2cai assenti da `cai_sections` restano (nessuna FK sul codice).
     *
     * @param  array<int, true>  $importable
     */
    private function importTrailSections(Connection $datapack, array $importable, bool $dryRun): CaiImportTableResult
    {
        $read = 0;
        $orphans = 0;
        $source = [];

        foreach ($datapack->table(CaiOsm2caiExtractWriter::TRAIL_SECTIONS_TABLE)->orderBy('trail_id')->orderBy('section_code')->cursor() as $row) {
            $read++;
            $trailId = (int) $row->trail_id;
            $code = trim((string) $row->section_code);

            // In dry-run i sentieri nuovi non sono in tabella: basta che siano fra quelli letti.
            if ($code === '' || mb_strlen($code) > 7 || (! $dryRun && ! isset($importable[$trailId]))) {
                $orphans++;

                continue;
            }

            $source[$trailId.'|'.$code] = ['cai_trail_id' => $trailId, 'section_code' => $code];
        }

        $current = [];

        foreach (DB::table('cai_trail_section')->cursor() as $row) {
            $current[$row->cai_trail_id.'|'.$row->section_code] = ['cai_trail_id' => (int) $row->cai_trail_id, 'section_code' => (string) $row->section_code];
        }

        $added = array_diff_key($source, $current);
        $removed = array_diff_key($current, $source);

        if (! $dryRun) {
            foreach (collect($removed)->groupBy('section_code') as $code => $links) {
                foreach ($links->pluck('cai_trail_id')->chunk(self::CHUNK) as $ids) {
                    DB::table('cai_trail_section')->where('section_code', $code)->whereIn('cai_trail_id', $ids->all())->delete();
                }
            }

            foreach (array_chunk(array_values($added), self::CHUNK) as $chunk) {
                DB::table('cai_trail_section')->insert($chunk);
            }
        }

        return new CaiImportTableResult(
            read: $read,
            created: count($added),
            skipped: $read - count($added),
            warnings: $orphans > 0 ? ["{$orphans} legami sentiero-Sezione senza sentiero importato o con codice non valido saltati"] : [],
            deleted: count($removed),
        );
    }

    /**
     * Riga di `cai_trails` dalla riga del datapack: date a `Y-m-d`, km a 3 decimali, percorribilità da `issues_status`,
     * riquadro dalla geometria.
     *
     * @return array<string, int|float|string|null>
     */
    private function candidate(stdClass $row): array
    {
        $text = fn (mixed $value): ?string => $value === null || trim((string) $value) === '' ? null : trim((string) $value);

        $candidate = [];

        foreach (self::INTEGER_COLUMNS as $column) {
            $candidate[$column] = $row->{$column} === null || $row->{$column} === '' ? null : (int) $row->{$column};
        }

        foreach (['ref', 'name', 'from_place', 'to_place', 'cai_scale', 'issues_status'] as $column) {
            $candidate[$column] = $text($row->{$column});
        }

        foreach (self::DATE_COLUMNS as $column) {
            $candidate[$column] = $text($row->{$column}) === null ? null : mb_substr((string) $text($row->{$column}), 0, 10);
        }

        $candidate['length_km'] = $row->length_km === null || $row->length_km === '' ? null : round((float) $row->length_km, 3);
        $candidate['practicability'] = CaiTrailPracticability::fromIssuesStatus($candidate['issues_status'])->value;
        $candidate['geometry'] = $text($row->geometry_geojson);

        $bounds = CaiGeoJsonBounds::of($candidate['geometry']);
        $candidate['min_lat'] = $bounds === null ? null : round($bounds->minLat, 6);
        $candidate['min_lng'] = $bounds === null ? null : round($bounds->minLng, 6);
        $candidate['max_lat'] = $bounds === null ? null : round($bounds->maxLat, 6);
        $candidate['max_lng'] = $bounds === null ? null : round($bounds->maxLng, 6);

        return $candidate;
    }

    /**
     * @param  array<string, int|float|string|null>  $candidate
     */
    private function differs(stdClass $current, array $candidate): bool
    {
        foreach ($candidate as $column => $value) {
            if ($this->comparable($column, $current->{$column} ?? null) !== $this->comparable($column, $value)) {
                return true;
            }
        }

        return false;
    }

    /** Stesso valore letto da Postgres (decimali come stringa, date) o da SQLite → stessa stringa. */
    private function comparable(string $column, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return match (true) {
            $column === 'length_km' => number_format((float) $value, 3, '.', ''),
            in_array($column, self::BOUNDS_COLUMNS, true) => number_format((float) $value, 6, '.', ''),
            in_array($column, self::INTEGER_COLUMNS, true) => (string) (int) $value,
            in_array($column, self::DATE_COLUMNS, true) => mb_substr((string) $value, 0, 10),
            default => (string) $value,
        };
    }
}
