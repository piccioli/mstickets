<?php

declare(strict_types=1);

namespace App\Support\Collaudo;

/**
 * Un riferimento a test del manifest di collaudo ha la forma
 * `percorso/al/file.php::descrizione del test` (§ "Processo di collaudo", CLAUDE.md):
 * il percorso serve a `collaudo:verify-manifest` per l'esistenza del file, la
 * descrizione per verificare che quel test specifico esista ancora al suo interno
 * (grep del contenuto). Questa classe è l'unica fonte dello split `::`, riusata da
 * comando di verifica, comando di generazione e PDF, per non duplicare la logica.
 */
final class CollaudoTestReference
{
    /**
     * Il solo percorso del file, senza il suffisso `::descrizione` (per il display).
     */
    public static function file(string $reference): string
    {
        return explode('::', $reference, 2)[0];
    }

    /**
     * La descrizione attesa del test, oppure null se il riferimento è un percorso nudo.
     */
    public static function description(string $reference): ?string
    {
        $parts = explode('::', $reference, 2);

        return isset($parts[1]) && $parts[1] !== '' ? $parts[1] : null;
    }
}
