<?php

declare(strict_types=1);

use App\Domain\Fundraising\Enums\FundraisingEvaluationCriterion;
use App\Domain\Fundraising\Services\CalculateEvaluationTotals;

test('sums only positive scores into the positive total', function (): void {
    $totals = CalculateEvaluationTotals::fromScores([
        FundraisingEvaluationCriterion::CriterionA->value => 4,
        FundraisingEvaluationCriterion::CriterionB->value => 3,
    ]);

    expect($totals)->toBe(['positive' => 7, 'negative' => 0, 'total' => 7]);
});

test('sums the absolute value of negative scores into the negative total', function (): void {
    $totals = CalculateEvaluationTotals::fromScores([
        FundraisingEvaluationCriterion::CriterionA->value => 4,
        FundraisingEvaluationCriterion::RiskFinanziari->value => -3,
        FundraisingEvaluationCriterion::RiskOrganizzativi->value => -2,
    ]);

    expect($totals)->toBe(['positive' => 4, 'negative' => 5, 'total' => -1]);
});

test('total is positive minus negative', function (): void {
    $totals = CalculateEvaluationTotals::fromScores([
        FundraisingEvaluationCriterion::CriterionA->value => 5,
        FundraisingEvaluationCriterion::RiskLogistici->value => -2,
    ]);

    expect($totals['total'])->toBe(3);
});

test('handles the min and max value of every catalog range', function (): void {
    foreach (FundraisingEvaluationCriterion::cases() as $criterion) {
        $min = CalculateEvaluationTotals::fromScores([$criterion->value => $criterion->min()]);
        $max = CalculateEvaluationTotals::fromScores([$criterion->value => $criterion->max()]);

        expect($min['total'])->toBe($criterion->min())
            ->and($max['total'])->toBe($criterion->max());
    }
});

test('a score of zero counts toward the positive total, not the negative one', function (): void {
    $totals = CalculateEvaluationTotals::fromScores([
        FundraisingEvaluationCriterion::RiskFinanziari->value => 0,
    ]);

    expect($totals)->toBe(['positive' => 0, 'negative' => 0, 'total' => 0]);
});

test('an empty set of scores totals to zero', function (): void {
    expect(CalculateEvaluationTotals::fromScores([]))
        ->toBe(['positive' => 0, 'negative' => 0, 'total' => 0]);
});

test('a criterion added to the catalog at runtime is included correctly without touching the database', function (): void {
    $totals = CalculateEvaluationTotals::fromScores([
        'criterion_a' => 5,
        'criterion_runtime_extra' => -4,
    ]);

    expect($totals)->toBe(['positive' => 5, 'negative' => 4, 'total' => 1]);
});
