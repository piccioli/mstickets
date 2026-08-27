<?php

declare(strict_types=1);

namespace App\Domain\Fundraising\Actions;

use App\Domain\Fundraising\Enums\FundraisingEvaluationCriterion;
use App\Domain\Fundraising\Models\FundraisingEvaluationScore;
use App\Domain\Fundraising\Models\FundraisingOpportunity;
use App\Domain\Fundraising\Services\CalculateEvaluationTotals;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Unico punto di ingresso per salvare i punteggi della griglia di valutazione (§6.6.2, US-503):
 * valida ogni punteggio contro il range del catalogo ({@see FundraisingEvaluationCriterion::min()}/
 * max()), upserta le righe di fundraising_evaluation_scores, poi ricalcola i totali con
 * {@see CalculateEvaluationTotals} sull'insieme COMPLETO dei punteggi persistiti (non solo quelli
 * passati a questa chiamata, per restare corretto anche con salvataggi parziali/incrementali della
 * griglia) — mai un hook saving()/saved() sul model. evaluated_by/evaluated_at si valorizzano solo
 * se l'opportunità non è ancora stata valutata, mai sovrascritti da salvataggi successivi.
 */
final class SaveEvaluationScores
{
    /**
     * @param  array<string, int>  $scores  criterion_key => punteggio
     * @param  array<string, string|null>  $notes  criterion_key => nota (rilevante solo per i criteri principali)
     */
    public static function run(
        FundraisingOpportunity $opportunity,
        array $scores,
        array $notes,
        User $actor,
    ): FundraisingOpportunity {
        return DB::transaction(function () use ($opportunity, $scores, $notes, $actor): FundraisingOpportunity {
            foreach ($scores as $key => $score) {
                $criterion = FundraisingEvaluationCriterion::from($key);

                if ($score < $criterion->min() || $score > $criterion->max()) {
                    throw new RuntimeException(sprintf(
                        'Il punteggio per "%s" deve essere compreso tra %d e %d.',
                        $criterion->getLabel(),
                        $criterion->min(),
                        $criterion->max(),
                    ));
                }

                FundraisingEvaluationScore::query()->updateOrCreate(
                    ['fundraising_opportunity_id' => $opportunity->id, 'criterion_key' => $criterion],
                    ['score' => $score, 'notes' => $notes[$key] ?? null],
                );
            }

            /** @var array<string, int> $persistedScores */
            $persistedScores = $opportunity->evaluationScores()
                ->get()
                ->mapWithKeys(fn (FundraisingEvaluationScore $score): array => [
                    $score->criterion_key->value => $score->score,
                ])
                ->all();

            $totals = CalculateEvaluationTotals::fromScores($persistedScores);

            $opportunity->evaluation_positive_total = $totals['positive'];
            $opportunity->evaluation_negative_total = $totals['negative'];
            $opportunity->evaluation_total = $totals['total'];

            if ($opportunity->evaluated_by === null) {
                $opportunity->evaluated_by = $actor->id;
                $opportunity->evaluated_at = now();
            }

            $opportunity->save();

            return $opportunity;
        });
    }
}
