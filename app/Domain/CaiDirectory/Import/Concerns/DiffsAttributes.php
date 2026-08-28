<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Confronto "riga esistente vs riga candidata" per l'idempotenza di `cai:import-datapack`
 * (US-802): una seconda esecuzione sullo stesso datapack non deve riscrivere righe
 * invariate (né bump di `updated_at`).
 *
 * Confrontare i valori grezzi provenienti da SQLite con quelli letti dalla connessione
 * applicativa (Postgres in dev/UAT, SQLite `:memory:` nei test) è fragile per le colonne
 * `decimal`: la stessa lat/lon può tornare formattata in modo diverso a seconda del
 * driver (es. "45.1234500" da Postgres vs "45.12345" da un float PHP grezzo), producendo
 * un "differisce" spurio ad ogni run. Passare entrambi i lati per gli stessi cast del
 * modello (`Model::newInstance()` sui nuovi attributi, mai persistito) normalizza il
 * confronto: entrambe le stringhe finali passano dallo stesso `casts()`.
 */
trait DiffsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function attributesDiffer(Model $existing, array $attributes): bool
    {
        $candidate = $existing->newInstance($attributes, false);

        foreach (array_keys($attributes) as $key) {
            $existingValue = (string) ($existing->getAttribute($key) ?? '');
            $candidateValue = (string) ($candidate->getAttribute($key) ?? '');

            if ($existingValue !== $candidateValue) {
                return true;
            }
        }

        return false;
    }
}
