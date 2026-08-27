<?php

declare(strict_types=1);

namespace App\Domain\Fundraising\Services;

/**
 * Service puro per il calcolo dei totali della griglia di valutazione (§6.6.2, US-503):
 * opera su una semplice mappa chiave => punteggio, senza dipendere dal catalogo enum né
 * dal DB, così che un criterio aggiunto solo a runtime venga comunque sommato correttamente.
 */
final class CalculateEvaluationTotals
{
    /**
     * @param  array<string, int>  $scores  criterion_key => punteggio
     * @return array{positive: int, negative: int, total: int}
     */
    public static function fromScores(array $scores): array
    {
        $positive = 0;
        $negative = 0;

        foreach ($scores as $score) {
            if ($score >= 0) {
                $positive += $score;
            } else {
                $negative += abs($score);
            }
        }

        return [
            'positive' => $positive,
            'negative' => $negative,
            'total' => $positive - $negative,
        ];
    }
}
