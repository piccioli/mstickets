<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Support;

/**
 * Nome breve di una Sezione per le note dell'elenco dei sentieri ("Condiviso con CAI Parma, SAT", US-1054), come nel
 * design "Sentieri di Sezione". Dal nome dell'anagrafica ("SEZ. PARMA", "SEZ. S.A.T.", "SEZ. MERANO C.A.I. A.A."):
 * toglie "SEZ." e il suffisso "C.A.I. A.A.", scrive le sigle puntate senza punti ("SAT") e il resto con le iniziali
 * maiuscole (preposizioni minuscole), preceduto da "CAI" se non comincia già con una sigla.
 */
final class CaiSectionShortName
{
    private const LOWERCASE = ['di', 'del', 'della', 'dei', 'delle', 'degli', 'dell', 'al', 'alla', 'in', 'e', 'sul', 'sulla'];

    public static function of(string $name): string
    {
        $name = trim((string) preg_replace(['/^SEZ(IONE)?\.?\s+/iu', '/\s*C\.A\.I\.?\s*A\.A\.?$/iu'], '', trim($name)));

        if ($name === '') {
            return 'CAI';
        }

        $tokens = preg_split('/\s+/u', $name) ?: [];

        return (self::isAcronym($tokens[0] ?? '') ? '' : 'CAI ').implode(' ', array_map(self::word(...), $tokens));
    }

    /** Sigla puntata: "S.A.T.", "C.A.A.I.". */
    private static function isAcronym(string $word): bool
    {
        return preg_match('/^(\p{Lu}\.){2,}\p{Lu}?\.?$/u', $word) === 1;
    }

    private static function word(string $word): string
    {
        if (self::isAcronym($word)) {
            return str_replace('.', '', $word);
        }

        $lower = mb_strtolower($word);

        if (in_array($lower, self::LOWERCASE, true) || preg_match("/^dell'/u", $lower) === 1) {
            return preg_replace_callback("/'(\p{Ll})/u", fn (array $m): string => "'".mb_strtoupper($m[1]), $lower) ?? $lower;
        }

        // Iniziale maiuscola anche dopo un punto o un trattino: "S.PIETRO" → "S.Pietro", "VERBANO-VERBANIA".
        return preg_replace_callback('/(^|[.\-])(\p{Ll})/u', fn (array $m): string => $m[1].mb_strtoupper($m[2]), $lower) ?? $lower;
    }
}
