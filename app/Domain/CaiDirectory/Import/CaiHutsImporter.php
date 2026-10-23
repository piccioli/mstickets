<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import;

use App\Domain\CaiDirectory\Enums\CaiHutStatus;
use App\Domain\CaiDirectory\Enums\CaiHutType;
use App\Domain\CaiDirectory\Import\Concerns\DiffsAttributes;
use App\Domain\CaiDirectory\Models\CaiHut;
use Illuminate\Database\Connection;

/**
 * Importa i rifugi della tabella `snap_cai_huts` del datapack (dettaglio grezzo dell'API rifugi.cai.it, scritto da
 * `cai:fetch-huts`) in `cai_huts` (US-1031). La fonte è di verità: crea, aggiorna ed **elimina** i rifugi non più
 * presenti. Idempotente (righe invariate non riscritte); `--dry-run` conta senza scrivere. Senza `snap_cai_huts` non
 * fa nulla (mai cancellare i rifugi per un datapack che non li contiene).
 */
final class CaiHutsImporter
{
    use DiffsAttributes;

    public const TABLE = 'snap_cai_huts';

    public function import(Connection $datapack, bool $dryRun): ?CaiImportTableResult
    {
        if (! $datapack->getSchemaBuilder()->hasTable(self::TABLE)) {
            return null;
        }

        $read = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $invalid = 0;
        $unknownType = 0;
        $unknownStatus = 0;
        $seen = [];

        foreach ($datapack->table(self::TABLE)->orderBy('id_cai')->cursor() as $row) {
            $read++;
            $idCai = (int) $row->id_cai;
            // Anche una riga illeggibile protegge il rifugio esistente dall'eliminazione.
            $seen[$idCai] = true;
            $detail = json_decode((string) $row->payload, true);

            if (! is_array($detail)) {
                $invalid++;
                $skipped++;

                continue;
            }

            $attributes = CaiHutFieldMapper::map($detail);

            if ($attributes['type'] === null && CaiHutFieldMapper::text($attributes['fields']['type'] ?? null) !== null) {
                $unknownType++;
            }

            if ($attributes['status'] === null && CaiHutFieldMapper::text($attributes['fields']['status'] ?? null) !== null) {
                $unknownStatus++;
            }

            $existing = CaiHut::query()->find($idCai);

            if ($existing === null) {
                if (! $dryRun) {
                    CaiHut::create(['id_cai' => $idCai, ...$attributes]);
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

        $stale = CaiHut::query()->pluck('id_cai')->map(fn ($id): int => (int) $id)->reject(fn (int $id): bool => isset($seen[$id]))->values();

        if (! $dryRun && $stale->isNotEmpty()) {
            $stale->chunk(500)->each(fn ($ids) => CaiHut::query()->whereKey($ids->all())->delete());
        }

        return new CaiImportTableResult(
            read: $read,
            created: $created,
            updated: $updated,
            skipped: $skipped,
            warnings: array_values(array_filter([
                $invalid > 0 ? "{$invalid} righe ".self::TABLE.' con JSON non valido saltate' : null,
                $unknownType > 0 ? "{$unknownType} rifugi con tipo non riconosciuto (tipo vuoto, testo in fields): valori noti ".implode(', ', array_column(CaiHutType::cases(), 'value')) : null,
                $unknownStatus > 0 ? "{$unknownStatus} rifugi con stato non riconosciuto (stato vuoto, testo in fields): valori noti ".implode(', ', array_column(CaiHutStatus::cases(), 'value')) : null,
            ])),
            deleted: $stale->count(),
        );
    }
}
