<?php

declare(strict_types=1);

use App\Domain\Fundraising\Actions\SaveEvaluationScores;
use App\Domain\Fundraising\Enums\FundraisingEvaluationCriterion;
use App\Domain\Fundraising\Models\FundraisingEvaluationScore;
use App\Domain\Fundraising\Models\FundraisingOpportunity;
use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeOpportunityForEvaluation(): FundraisingOpportunity
{
    $user = User::factory()->create();

    return FundraisingOpportunity::create([
        'name' => 'Bando test',
        'deadline' => '2026-12-31',
        'created_by' => $user->id,
        'responsible_user_id' => $user->id,
    ]);
}

test('persists a score row per criterion and computes totals from all persisted scores', function (): void {
    $opportunity = makeOpportunityForEvaluation();
    $actor = User::factory()->create();

    $result = SaveEvaluationScores::run($opportunity, [
        FundraisingEvaluationCriterion::CriterionA->value => 4,
        FundraisingEvaluationCriterion::RiskFinanziari->value => -2,
    ], [], $actor);

    expect(FundraisingEvaluationScore::query()->count())->toBe(2)
        ->and($result->evaluation_positive_total)->toBe(4)
        ->and($result->evaluation_negative_total)->toBe(2)
        ->and($result->evaluation_total)->toBe(2);
});

test('re-saving a criterion updates the existing row instead of duplicating it', function (): void {
    $opportunity = makeOpportunityForEvaluation();
    $actor = User::factory()->create();

    SaveEvaluationScores::run($opportunity, [FundraisingEvaluationCriterion::CriterionA->value => 2], [], $actor);
    SaveEvaluationScores::run($opportunity, [FundraisingEvaluationCriterion::CriterionA->value => 5], [], $actor);

    expect(FundraisingEvaluationScore::query()->count())->toBe(1)
        ->and($opportunity->fresh()->evaluation_total)->toBe(5);
});

test('totals accumulate scores saved across multiple calls', function (): void {
    $opportunity = makeOpportunityForEvaluation();
    $actor = User::factory()->create();

    SaveEvaluationScores::run($opportunity, [FundraisingEvaluationCriterion::CriterionA->value => 3], [], $actor);
    $result = SaveEvaluationScores::run($opportunity, [FundraisingEvaluationCriterion::CriterionB->value => 2], [], $actor);

    expect($result->evaluation_total)->toBe(5);
});

test('rejects a score below the catalog minimum', function (): void {
    $opportunity = makeOpportunityForEvaluation();
    $actor = User::factory()->create();

    expect(fn () => SaveEvaluationScores::run(
        $opportunity,
        [FundraisingEvaluationCriterion::CriterionA->value => -1],
        [],
        $actor,
    ))->toThrow(RuntimeException::class);

    expect(FundraisingEvaluationScore::query()->count())->toBe(0);
});

test('rejects a score above the catalog maximum', function (): void {
    $opportunity = makeOpportunityForEvaluation();
    $actor = User::factory()->create();

    expect(fn () => SaveEvaluationScores::run(
        $opportunity,
        [FundraisingEvaluationCriterion::BaseCoerenzaBando->value => 2],
        [],
        $actor,
    ))->toThrow(RuntimeException::class);
});

test('accepts a score at the exact min and max of its range', function (): void {
    $opportunity = makeOpportunityForEvaluation();
    $actor = User::factory()->create();

    $result = SaveEvaluationScores::run($opportunity, [
        FundraisingEvaluationCriterion::RiskFinanziari->value => -3,
    ], [], $actor);
    expect($result->evaluation_negative_total)->toBe(3);

    $result = SaveEvaluationScores::run($opportunity, [
        FundraisingEvaluationCriterion::RiskFinanziari->value => 3,
    ], [], $actor);
    expect($result->evaluation_positive_total)->toBe(3);
});

test('sets evaluated_by and evaluated_at on the first saved score', function (): void {
    $opportunity = makeOpportunityForEvaluation();
    $actor = User::factory()->create();

    expect($opportunity->evaluated_by)->toBeNull();

    $result = SaveEvaluationScores::run($opportunity, [FundraisingEvaluationCriterion::CriterionA->value => 3], [], $actor);

    expect($result->evaluated_by)->toBe($actor->id)
        ->and($result->evaluated_at)->not->toBeNull();
});

test('does not overwrite evaluated_by/evaluated_at on subsequent saves', function (): void {
    $opportunity = makeOpportunityForEvaluation();
    $firstActor = User::factory()->create();
    $secondActor = User::factory()->create();

    $first = SaveEvaluationScores::run($opportunity, [FundraisingEvaluationCriterion::CriterionA->value => 3], [], $firstActor);
    $firstEvaluatedAt = $first->evaluated_at;

    test()->travel(1)->hours();

    $second = SaveEvaluationScores::run($opportunity, [FundraisingEvaluationCriterion::CriterionB->value => 4], [], $secondActor);

    expect($second->evaluated_by)->toBe($firstActor->id)
        ->and($second->evaluated_at->equalTo($firstEvaluatedAt))->toBeTrue();
});

test('persists notes for a criterion', function (): void {
    $opportunity = makeOpportunityForEvaluation();
    $actor = User::factory()->create();

    SaveEvaluationScores::run(
        $opportunity,
        [FundraisingEvaluationCriterion::CriterionA->value => 4],
        [FundraisingEvaluationCriterion::CriterionA->value => 'Nota di valutazione'],
        $actor,
    );

    expect(FundraisingEvaluationScore::query()->first()->notes)->toBe('Nota di valutazione');
});
