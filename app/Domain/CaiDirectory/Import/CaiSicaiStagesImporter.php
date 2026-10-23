<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import;

use Illuminate\Database\Connection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Importa le tappe del Sentiero Italia CAI del datapack (`snap_osm2cai_sicai_stages`, scritta da
 * `bin/update-osm2cai-datapack`) in `cai_sicai_stages` (US-1091), riga `snapshot_tappe_sicai`. Va eseguito DOPO
 * {@see CaiTrailsImporter}: la tappa è un sentiero di `cai_trails`. La fonte è di verità: crea, aggiorna ed **elimina**
 * le tappe non più presenti. Idempotente (righe invariate non riscritte); `--dry-run` conta senza scrivere. Tappe di
 * sentieri non importati saltate con avviso; senza la tabella il passo è saltato con un avviso (mai cancellare le tappe
 * per un datapack che non le contiene).
 */
final class CaiSicaiStagesImporter
{
    public const RESULT_KEY = 'snapshot_tappe_sicai';

    private const COLUMNS = ['itinerary_name', 'letter', 'number', 'variant', 'code', 'sort_key'];

    /**
     * @return array<string, CaiImportTableResult>
     */
    public function import(Connection $datapack, bool $dryRun): array
    {
        if (! $datapack->getSchemaBuilder()->hasTable(CaiOsm2caiExtractWriter::SICAI_STAGES_TABLE)) {
            return [self::RESULT_KEY => new CaiImportTableResult(
                warnings: ['Tabella '.CaiOsm2caiExtractWriter::SICAI_STAGES_TABLE.' assente nel datapack: tappe SICAI non aggiornate (bin/update-osm2cai-datapack)'],
            )];
        }

        $run = fn (): CaiImportTableResult => $this->run($datapack, $dryRun);

        return [self::RESULT_KEY => $dryRun ? $run() : DB::transaction($run)];
    }

    private function run(Connection $datapack, bool $dryRun): CaiImportTableResult
    {
        $existing = DB::table('cai_sicai_stages')->get()->keyBy('cai_trail_id');
        $trailIds = array_flip(DB::table('cai_trails')->pluck('id')->map(fn ($id): int => (int) $id)->all());
        $now = Carbon::now();

        $read = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $orphans = 0;
        $seen = [];
        $inserts = [];

        foreach ($datapack->table(CaiOsm2caiExtractWriter::SICAI_STAGES_TABLE)->orderBy('trail_id')->cursor() as $row) {
            $read++;
            $id = (int) $row->trail_id;
            // In dry-run i sentieri nuovi non sono ancora in `cai_trails`: li si controlla solo scrivendo.
            if (! $dryRun && ! isset($trailIds[$id])) {
                $orphans++;
                $skipped++;

                continue;
            }

            $seen[$id] = true;
            $candidate = $this->candidate($row);
            $current = $existing->get($id);

            if ($current === null) {
                $inserts[] = ['cai_trail_id' => $id, ...$candidate, 'created_at' => $now, 'updated_at' => $now];
                $created++;

                continue;
            }

            if ($this->differs($current, $candidate)) {
                if (! $dryRun) {
                    DB::table('cai_sicai_stages')->where('cai_trail_id', $id)->update([...$candidate, 'updated_at' => $now]);
                }
                $updated++;

                continue;
            }

            $skipped++;
        }

        $stale = $existing->keys()->map(fn ($id): int => (int) $id)->reject(fn (int $id): bool => isset($seen[$id]))->values()->all();

        if (! $dryRun) {
            foreach (array_chunk($inserts, 500) as $chunk) {
                DB::table('cai_sicai_stages')->insert($chunk);
            }

            foreach (array_chunk($stale, 500) as $ids) {
                DB::table('cai_sicai_stages')->whereIn('cai_trail_id', $ids)->delete();
            }
        }

        return new CaiImportTableResult(
            read: $read,
            created: $created,
            updated: $updated,
            skipped: $skipped,
            warnings: $orphans > 0 ? ["{$orphans} tappe SICAI di sentieri non importati saltate"] : [],
            deleted: count($stale),
        );
    }

    /**
     * @return array<string, int|string|null>
     */
    private function candidate(stdClass $row): array
    {
        $text = fn (mixed $value): ?string => $value === null || trim((string) $value) === '' ? null : trim((string) $value);

        return [
            'itinerary_name' => (string) $text($row->itinerary_name),
            'letter' => $text($row->letter),
            'number' => $row->number === null || $row->number === '' ? null : (int) $row->number,
            'variant' => $text($row->variant),
            'code' => (string) $text($row->code),
            'sort_key' => (string) $text($row->sort_key),
        ];
    }

    /**
     * @param  array<string, int|string|null>  $candidate
     */
    private function differs(stdClass $current, array $candidate): bool
    {
        foreach (self::COLUMNS as $column) {
            $value = $current->{$column} ?? null;

            if (($value === null ? null : (string) $value) !== ($candidate[$column] === null ? null : (string) $candidate[$column])) {
                return true;
            }
        }

        return false;
    }
}
