<?php

declare(strict_types=1);

namespace App\Domain\CaiDirectory\Import;

use Carbon\Carbon;
use Throwable;

/**
 * Parsing delle date testuali del datapack RUNTS-CAI (US-802). `enti.data_iscrizione`
 * NON è uniformemente `DD/MM/YYYY`: sul dataset reale 68/184 righe sono narrative libere
 * che incorporano la data in coda, es. "Iscritto tramite trasmigrazione il 24/02/2023".
 * Estrae sempre l'ULTIMA sottostringa `DD/MM/YYYY` trovata nel testo, mai l'intera
 * stringa presa alla lettera — se nessuna sottostringa "a forma di data" è presente,
 * o non è una data di calendario valida, ritorna null piuttosto che sollevare
 * un'eccezione (un campo data mancante non deve mai far fallire l'intero import).
 */
final class CaiRuntsDateParser
{
    private const DMY_PATTERN = '/(\d{2})\/(\d{2})\/(\d{4})/';

    public static function parse(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        preg_match_all(self::DMY_PATTERN, $raw, $matches, PREG_SET_ORDER);

        if ($matches === []) {
            return null;
        }

        /** @var array{0: string, 1: string, 2: string, 3: string} $last */
        $last = end($matches);
        [, $day, $month, $year] = $last;

        return self::toIsoDate($day, $month, $year);
    }

    private static function toIsoDate(string $day, string $month, string $year): ?string
    {
        try {
            $date = Carbon::createFromFormat('d/m/Y', "{$day}/{$month}/{$year}");
        } catch (Throwable) {
            return null;
        }

        if (! $date instanceof Carbon) {
            return null;
        }

        // Carbon::createFromFormat è permissivo (es. 31/02 trabocca a marzo): scartiamo
        // qualunque data che non "torna" esattamente al testo estratto, invece di
        // importare una data di calendario diversa da quella scritta nella fonte.
        if ($date->format('d/m/Y') !== "{$day}/{$month}/{$year}") {
            return null;
        }

        return $date->format('Y-m-d');
    }
}
