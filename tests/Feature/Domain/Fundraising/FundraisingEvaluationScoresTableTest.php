<?php

declare(strict_types=1);

use App\Domain\Fundraising\Enums\FundraisingEvaluationCriterion;
use App\Domain\Fundraising\Models\FundraisingEvaluationScore;
use App\Domain\Fundraising\Models\FundraisingOpportunity;
use App\Domain\Identity\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function makeFundraisingOpportunity(): FundraisingOpportunity
{
    $user = User::factory()->create();

    return FundraisingOpportunity::create([
        'name' => 'Bando test',
        'deadline' => '2026-12-31',
        'created_by' => $user->id,
        'responsible_user_id' => $user->id,
    ]);
}

test('fundraising_evaluation_scores table has the columns required by §5.2', function (): void {
    expect(Schema::hasColumns('fundraising_evaluation_scores', [
        'id', 'fundraising_opportunity_id', 'criterion_key', 'score', 'notes', 'created_at', 'updated_at',
    ]))->toBeTrue();
});

test('casts criterion_key to the FundraisingEvaluationCriterion enum and belongs to the opportunity', function (): void {
    $opportunity = makeFundraisingOpportunity();

    $score = FundraisingEvaluationScore::create([
        'fundraising_opportunity_id' => $opportunity->id,
        'criterion_key' => FundraisingEvaluationCriterion::CriterionA,
        'score' => 4,
    ]);

    expect($score->fresh()->criterion_key)->toBe(FundraisingEvaluationCriterion::CriterionA)
        ->and($score->fundraisingOpportunity->is($opportunity))->toBeTrue();
});

test('unique on the opportunity/criterion pair', function (): void {
    $opportunity = makeFundraisingOpportunity();

    DB::table('fundraising_evaluation_scores')->insert([
        'fundraising_opportunity_id' => $opportunity->id,
        'criterion_key' => FundraisingEvaluationCriterion::CriterionA->value,
        'score' => 4,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => DB::table('fundraising_evaluation_scores')->insert([
        'fundraising_opportunity_id' => $opportunity->id,
        'criterion_key' => FundraisingEvaluationCriterion::CriterionA->value,
        'score' => 2,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});

test('cascades on opportunity delete', function (): void {
    $opportunity = makeFundraisingOpportunity();

    FundraisingEvaluationScore::create([
        'fundraising_opportunity_id' => $opportunity->id,
        'criterion_key' => FundraisingEvaluationCriterion::RiskFinanziari,
        'score' => -2,
    ]);

    $opportunity->delete();

    expect(DB::table('fundraising_evaluation_scores')->count())->toBe(0);
});
