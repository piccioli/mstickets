<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import;

/**
 * Esito di {@see CaiTaxCodeFallbackGenerator::generate()}: `entries` è la mappa da
 * scrivere nel JSON di fallback (`codice_cai` => nome/CF/PIVA), `unmatchedNames` sono le
 * righe del foglio Excel che non hanno trovato una `CaiSection` corrispondente (es. i
 * "Gruppi Regionali", che non sono sezioni, o nomi troppo diversi dal dato RUNTS-CAI per
 * il confronto normalizzato) — stampate come warning dal comando, mai scartate in
 * silenzio.
 */
final readonly class CaiTaxCodeFallbackGenerationResult
{
    /**
     * @param  array<string, array{name: string, tax_code: ?string, vat_number: ?string}>  $entries
     * @param  list<string>  $unmatchedNames
     */
    public function __construct(
        public array $entries,
        public array $unmatchedNames,
    ) {}
}
