<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import;

use App\Domain\CaiDirectory\Import\Concerns\DiffsAttributes;
use App\Domain\CaiDirectory\Models\CaiHut;
use App\Domain\CaiDirectory\Models\CaiSicaiRestPoint;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

/**
 * Importa i punti di appoggio SICAI della tabella `snap_cai_sicai_rest_points` del datapack (dettaglio grezzo dell'API
 * rifugi.cai.it, scritto da `cai:fetch-sicai-rest-points`) in `cai_sicai_rest_points` (US-1092), riga
 * `snapshot_punti_appoggio_sicai`. Va eseguito DOPO {@see CaiHutsImporter}: `shelter_id` (id interno della fonte) si
 * traduce in `id_cai` con i payload di `snap_cai_huts` e si tiene solo se il rifugio è in `cai_huts`. La fonte è di
 * verità: crea, aggiorna ed **elimina**. Idempotente e in una transazione; `--dry-run` conta senza scrivere. Senza la tabella non fa nulla.
 */
final class CaiSicaiRestPointsImporter
{
    use DiffsAttributes;

    public const TABLE = 'snap_cai_sicai_rest_points';

    public const RESULT_KEY = 'snapshot_punti_appoggio_sicai';

    public function import(Connection $datapack, bool $dryRun): ?CaiImportTableResult
    {
        if (! $datapack->getSchemaBuilder()->hasTable(self::TABLE)) {
            return null;
        }

        $run = fn (): CaiImportTableResult => $this->run($datapack, $dryRun);

        // In una transazione: un punto che non si scrive non lascia l'import a metà.
        return $dryRun ? $run() : DB::transaction($run);
    }

    private function run(Connection $datapack, bool $dryRun): CaiImportTableResult
    {
        $hutIdCaiByShelterId = $this->hutIdCaiByShelterId($datapack);

        $read = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $invalid = 0;
        $unlinkedShelters = 0;
        $seen = [];

        foreach ($datapack->table(self::TABLE)->orderBy('id')->cursor() as $row) {
            $read++;
            $id = (int) $row->id;
            // Anche una riga illeggibile protegge il punto esistente dall'eliminazione.
            $seen[$id] = true;
            $detail = json_decode((string) $row->payload, true);

            if (! is_array($detail)) {
                $invalid++;
                $skipped++;

                continue;
            }

            $shelterId = CaiSicaiRestPointFieldMapper::shelterId($detail);
            $hutIdCai = $shelterId === null ? null : ($hutIdCaiByShelterId[$shelterId] ?? null);

            if ($shelterId !== null && $hutIdCai === null) {
                $unlinkedShelters++;
            }

            $attributes = [...CaiSicaiRestPointFieldMapper::map($detail), 'cai_hut_id_cai' => $hutIdCai];
            $existing = CaiSicaiRestPoint::query()->find($id);

            if ($existing === null) {
                if (! $dryRun) {
                    CaiSicaiRestPoint::create(['id' => $id, ...$attributes]);
                }
                $created++;

                continue;
            }

            if ($this->attributesDiffer($existing, $attributes)) {
                if (! $dryRun) {
                    $existing->update($attributes);
                }
                $updated++;

                continue;
            }

            $skipped++;
        }

        $stale = CaiSicaiRestPoint::query()->pluck('id')->map(fn ($id): int => (int) $id)->reject(fn (int $id): bool => isset($seen[$id]))->values();

        if (! $dryRun && $stale->isNotEmpty()) {
            $stale->chunk(500)->each(fn ($ids) => CaiSicaiRestPoint::query()->whereKey($ids->all())->delete());
        }

        return new CaiImportTableResult(
            read: $read,
            created: $created,
            updated: $updated,
            skipped: $skipped,
            warnings: array_values(array_filter([
                $invalid > 0 ? "{$invalid} righe ".self::TABLE.' con JSON non valido saltate' : null,
                $unlinkedShelters > 0 ? "{$unlinkedShelters} punti di appoggio con shelter_id di un rifugio non importato: collegamento al rifugio vuoto" : null,
            ])),
            deleted: $stale->count(),
        );
    }

    /**
     * `id` interno della fonte → `id_cai` dei rifugi di `snap_cai_huts` presenti in `cai_huts`.
     *
     * @return array<int, int>
     */
    private function hutIdCaiByShelterId(Connection $datapack): array
    {
        if (! $datapack->getSchemaBuilder()->hasTable(CaiHutsImporter::TABLE)) {
            return [];
        }

        $imported = array_flip(CaiHut::query()->pluck('id_cai')->map(fn ($id): int => (int) $id)->all());
        $map = [];

        $rows = $datapack->table(CaiHutsImporter::TABLE)
            ->selectRaw("id_cai, json_extract(payload, '$.id') AS source_id")
            ->cursor();

        foreach ($rows as $row) {
            if ($row->source_id !== null && isset($imported[(int) $row->id_cai])) {
                $map[(int) $row->source_id] = (int) $row->id_cai;
            }
        }

        return $map;
    }
}
