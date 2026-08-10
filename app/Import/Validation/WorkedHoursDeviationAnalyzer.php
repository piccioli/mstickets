<?php

declare(strict_types=1);

namespace App\Import\Validation;

/**
 * Confronta le ore lavorate v1 (`stories.hours`) con i minuti lavorati ricalcolati in
 * v2 (`tickets.worked_minutes`, stage `derive`/US-215), ticket per ticket. Un ticket è
 * entro tolleranza se lo scostamento è entro la soglia **percentuale** (±5% di default,
 * assunzione per Q6 del PRD) **oppure** entro la soglia **assoluta** in minuti (15' di
 * default): la sola percentuale non è un criterio sensato su valori piccoli (un ticket
 * v1 da 10 minuti che in v2 arrotonda a 0 per la granularità configurata risulta "100%
 * di scostamento" pur essendo una differenza reale di pochi minuti, rumore di
 * arrotondamento — non un problema di dominio, verificato sul dump v1 reale in
 * US-219). Puro/senza I/O: il chiamante (v1:validate, US-216) legge le due colonne e
 * passa qui solo le coppie già accoppiate per id.
 */
final class WorkedHoursDeviationAnalyzer
{
    /**
     * @param  array<int, array{id:int, v1_hours: float|null, v2_minutes: int}>  $rows
     * @return array{
     *     compared:int,
     *     skipped_no_v1_hours:int,
     *     within_tolerance:int,
     *     beyond_tolerance:array<int, array{id:int, v1_hours:float, v2_hours:float, deviation_percent:float, deviation_minutes:float}>,
     *     min_deviation_percent:float,
     *     max_deviation_percent:float,
     *     avg_deviation_percent:float,
     * }
     */
    public static function analyze(array $rows, float $tolerance = 0.05, int $toleranceAbsoluteMinutes = 15): array
    {
        $compared = 0;
        $skippedNoV1Hours = 0;
        $withinTolerance = 0;
        $beyondTolerance = [];
        $deviations = [];

        foreach ($rows as $row) {
            $v1Hours = $row['v1_hours'];

            // Un ticket senza ore v1 (null o zero) non è confrontabile: la tolleranza
            // relativa (±5%) non è definita per un denominatore zero, e "0 ore lavorate
            // in v1" non è un dato interessante da conciliare con v2 (nessuno storico da
            // verificare).
            if ($v1Hours === null || $v1Hours <= 0.0) {
                $skippedNoV1Hours++;

                continue;
            }

            $compared++;

            $v1Minutes = $v1Hours * 60;
            $v2Hours = $row['v2_minutes'] / 60;
            $deviationMinutes = abs($row['v2_minutes'] - $v1Minutes);
            $deviation = $deviationMinutes / $v1Minutes;
            $deviations[] = $deviation;

            if ($deviation > $tolerance && $deviationMinutes > $toleranceAbsoluteMinutes) {
                $beyondTolerance[] = [
                    'id' => $row['id'],
                    'v1_hours' => $v1Hours,
                    'v2_hours' => round($v2Hours, 2),
                    'deviation_percent' => round($deviation * 100, 2),
                    'deviation_minutes' => round($deviationMinutes, 2),
                ];

                continue;
            }

            $withinTolerance++;
        }

        return [
            'compared' => $compared,
            'skipped_no_v1_hours' => $skippedNoV1Hours,
            'within_tolerance' => $withinTolerance,
            'beyond_tolerance' => $beyondTolerance,
            'min_deviation_percent' => $deviations === [] ? 0.0 : round(min($deviations) * 100, 2),
            'max_deviation_percent' => $deviations === [] ? 0.0 : round(max($deviations) * 100, 2),
            'avg_deviation_percent' => $deviations === [] ? 0.0 : round((array_sum($deviations) / count($deviations)) * 100, 2),
        ];
    }
}
