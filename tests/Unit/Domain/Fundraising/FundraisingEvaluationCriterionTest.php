<?php

declare(strict_types=1);

use App\Domain\Fundraising\Enums\FundraisingEvaluationCriterion;
use Filament\Support\Contracts\HasLabel;

test('contains exactly the 26 criteria of §6.6.2', function (): void {
    expect(FundraisingEvaluationCriterion::cases())->toHaveCount(26);
});

test('every case has a non-empty label and a group', function (): void {
    foreach (FundraisingEvaluationCriterion::cases() as $criterion) {
        expect($criterion)->toBeInstanceOf(HasLabel::class);
        expect($criterion->getLabel())->not->toBeEmpty();
        expect($criterion->group())->not->toBeEmpty();
    }
});

test('main criteria range 0 to 5', function (): void {
    expect(FundraisingEvaluationCriterion::CriterionA->min())->toBe(0)
        ->and(FundraisingEvaluationCriterion::CriterionA->max())->toBe(5);
});

test('base requirement criteria range 0 to 1', function (): void {
    expect(FundraisingEvaluationCriterion::BaseCoerenzaBando->min())->toBe(0)
        ->and(FundraisingEvaluationCriterion::BaseCoerenzaBando->max())->toBe(1);
});

test('risk criteria allow negative scores per §6.6.2', function (): void {
    expect(FundraisingEvaluationCriterion::RiskFinanziari->min())->toBe(-3)
        ->and(FundraisingEvaluationCriterion::RiskFinanziari->max())->toBe(3);

    expect(FundraisingEvaluationCriterion::RiskOrganizzativi->min())->toBe(-2)
        ->and(FundraisingEvaluationCriterion::RiskOrganizzativi->max())->toBe(2);

    expect(FundraisingEvaluationCriterion::RiskLogistici->min())->toBe(-2)
        ->and(FundraisingEvaluationCriterion::RiskLogistici->max())->toBe(2);

    expect(FundraisingEvaluationCriterion::RiskTecnici->min())->toBe(0)
        ->and(FundraisingEvaluationCriterion::RiskTecnici->max())->toBe(3);
});
