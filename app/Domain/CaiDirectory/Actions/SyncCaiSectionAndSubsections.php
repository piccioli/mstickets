<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Actions;

use App\Domain\CaiDirectory\Import\CaiApiSectionNormalizer;
use App\Domain\CaiDirectory\Import\CaiImportTableResult;
use App\Domain\CaiDirectory\Import\CaiSectionFieldMapper;
use App\Domain\CaiDirectory\Import\Concerns\DiffsAttributes;
use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Models\CaiSubsection;
use App\Domain\CaiDirectory\Support\CaiApiClient;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Sincronizza dal vivo UNA `CaiSection` (e le sue sottosezioni) a partire da una riga
 * già normalizzata dell'API CAI (Fase 9, storia 1, design doc §3.1/§3.4/§3.5) —
 * "va estratta e condivisa, non duplicata": riusata sia da {@see ScrapeCaiSection}
 * (una sola sezione, bottone dashboard) sia da `CaiSyncNationalCommand` (loop su
 * tutte le sezioni, refresh mensile).
 *
 * `cai_last_synced_at` viene sempre valorizzato a `now()` (sia sulla sezione sia su
 * ciascuna sottosezione) quando NON in dry-run, indipendentemente dal fatto che i
 * campi di business siano cambiati: contattare la fonte e confermare che i dati sono
 * invariati resta comunque una sincronizzazione avvenuta. I contatori
 * created/updated/skipped restano invece basati SOLO sul confronto dei campi di
 * business (via {@see DiffsAttributes}, che non include mai `cai_last_synced_at`
 * nell'array `$attributes` confrontato) — altrimenti ogni riga risulterebbe sempre
 * "updated" per il solo bump del timestamp, rendendo il conteggio inutile per la
 * dashboard/notifica del bottone (che lo usa per decidere il testo "aggiornato" vs
 * "già aggiornato").
 */
final class SyncCaiSectionAndSubsections
{
    use DiffsAttributes;

    public function __construct(private readonly CaiApiClient $apiClient) {}

    /**
     * @return array{cai_sections: CaiImportTableResult, cai_subsections: CaiImportTableResult}
     */
    public function run(object $normalizedSectionRow, bool $dryRun = false): array
    {
        $usersByLowerEmail = $this->buildUsersByLowerEmail();

        return [
            'cai_sections' => $this->syncSection($normalizedSectionRow, $usersByLowerEmail, $dryRun),
            'cai_subsections' => $this->syncSubsections($normalizedSectionRow->codice_cai, $usersByLowerEmail, $dryRun),
        ];
    }

    /**
     * @param  array<string, int>  $usersByLowerEmail
     */
    private function syncSection(object $row, array $usersByLowerEmail, bool $dryRun): CaiImportTableResult
    {
        $attributes = CaiSectionFieldMapper::mapSection($row, $usersByLowerEmail);
        $existing = CaiSection::find((string) $row->codice_cai);

        if ($existing === null) {
            if (! $dryRun) {
                CaiSection::create(['codice_cai' => $row->codice_cai, ...$attributes, 'cai_last_synced_at' => Carbon::now()]);
            }

            return new CaiImportTableResult(read: 1, created: 1);
        }

        $changed = $this->attributesDiffer($existing, $attributes);

        if (! $dryRun) {
            $existing->fill($attributes);
            $existing->cai_last_synced_at = Carbon::now();
            $existing->save();
        }

        return $changed
            ? new CaiImportTableResult(read: 1, updated: 1)
            : new CaiImportTableResult(read: 1, skipped: 1);
    }

    /**
     * @param  array<string, int>  $usersByLowerEmail
     */
    private function syncSubsections(string $sectionCode, array $usersByLowerEmail, bool $dryRun): CaiImportTableResult
    {
        $rawSubsections = $this->apiClient->fetchSubsections($sectionCode);

        $read = 0;
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rawSubsections as $raw) {
            $read++;

            $row = CaiApiSectionNormalizer::normalizeSubsection($raw, $sectionCode);
            $attributes = CaiSectionFieldMapper::mapSubsection($row, $usersByLowerEmail);
            $existing = CaiSubsection::find((string) $row->cai_codice);

            if ($existing === null) {
                $created++;

                if (! $dryRun) {
                    CaiSubsection::create(['cai_codice' => $row->cai_codice, ...$attributes, 'cai_last_synced_at' => Carbon::now()]);
                }

                continue;
            }

            $changed = $this->attributesDiffer($existing, $attributes);
            $changed ? $updated++ : $skipped++;

            if (! $dryRun) {
                $existing->fill($attributes);
                $existing->cai_last_synced_at = Carbon::now();
                $existing->save();
            }
        }

        return new CaiImportTableResult(read: $read, created: $created, updated: $updated, skipped: $skipped);
    }

    /**
     * @return array<string, int>
     */
    private function buildUsersByLowerEmail(): array
    {
        return User::query()
            ->whereNotNull('email')
            ->get(['id', 'email'])
            ->mapWithKeys(fn (User $user): array => [Str::lower(trim((string) $user->email)) => $user->id])
            ->all();
    }
}
