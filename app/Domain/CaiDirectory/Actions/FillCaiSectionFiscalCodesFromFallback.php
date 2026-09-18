<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Actions;

use App\Domain\CaiDirectory\Import\CaiImportTableResult;
use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Support\CaiTaxCodeFallbackRepository;
use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Completa `tax_code`/`vat_number` mancanti su `CaiSection` a partire dal fallback
 * manuale (`CaiTaxCodeFallbackRepository`, generato da `cai:generate-tax-code-fallback`
 * a partire da un foglio Excel del committente) — mai una fonte primaria: usata solo
 * quando il datapack RUNTS-CAI/la sincronizzazione live non hanno già valorizzato il
 * campo. Riempie SOLO i campi effettivamente vuoti, mai sovrascrive un valore già
 * presente (anche se il fallback ne contiene uno diverso: il dato già in anagrafica,
 * qualunque origine abbia, resta quello di riferimento).
 *
 * Riusata sia da `cai:fill-tax-codes-from-fallback` (comando standalone) sia in testa a
 * `cai:sync-runts-all`, come fallback prima del giro di sincronizzazione live.
 */
final class FillCaiSectionFiscalCodesFromFallback
{
    public function __construct(private readonly CaiTaxCodeFallbackRepository $fallback) {}

    public function run(User $actor, bool $dryRun = false): CaiImportTableResult
    {
        $sections = CaiSection::query()
            ->where(fn (Builder $query): Builder => $query
                ->whereNull('tax_code')->orWhere('tax_code', '')
                ->orWhereNull('vat_number')->orWhere('vat_number', ''))
            ->get();

        $read = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($sections as $section) {
            $read++;

            $entry = $this->fallback->forCode($section->codice_cai);
            $attributes = $this->missingAttributes($section, $entry);

            if ($attributes === []) {
                $skipped++;

                continue;
            }

            $updated++;

            if (! $dryRun) {
                $section->fill($attributes)->save();
            }
        }

        return new CaiImportTableResult(read: $read, updated: $updated, skipped: $skipped);
    }

    /**
     * @param  array{name: string, tax_code: ?string, vat_number: ?string}|null  $entry
     * @return array<string, string>
     */
    private function missingAttributes(CaiSection $section, ?array $entry): array
    {
        if ($entry === null) {
            return [];
        }

        $attributes = [];

        if ($this->isBlank($section->tax_code) && ! $this->isBlank($entry['tax_code'] ?? null)) {
            $attributes['tax_code'] = $entry['tax_code'];
        }

        if ($this->isBlank($section->vat_number) && ! $this->isBlank($entry['vat_number'] ?? null)) {
            $attributes['vat_number'] = $entry['vat_number'];
        }

        return $attributes;
    }

    private function isBlank(?string $value): bool
    {
        return $value === null || trim($value) === '';
    }
}
