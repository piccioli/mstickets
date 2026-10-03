<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import\ManualBilancio;

/**
 * Scompone il nome di un file normalizzato `<codice> - CAI <Nome> - <Etichetta>.<ext>` (campagna "Bilanci
 * Sezioni 2026"). Restituisce `null` se il nome non combacia: mai un'eccezione, il chiamante registra
 * un'anomalia.
 */
final class ManualBilancioFilenameParser
{
    public static function parse(string $fileName): ?ParsedManualBilancioFilename
    {
        if (preg_match('/^(\d{7}) - (.+?) - (.+)\.(\w+)$/u', $fileName, $m) !== 1) {
            return null;
        }

        return new ParsedManualBilancioFilename(
            codiceCai: $m[1],
            sectionLabel: $m[2],
            label: $m[3],
            extension: $m[4],
        );
    }
}
