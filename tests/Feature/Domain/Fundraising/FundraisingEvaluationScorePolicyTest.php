<?php

declare(strict_types=1);

use App\Domain\Fundraising\Models\FundraisingEvaluationScore;
use App\Domain\Fundraising\Models\FundraisingOpportunity;
use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeFundraisingEvaluationScore(): FundraisingEvaluationScore
{
    $creator = User::factory()->create();
    $opportunity = FundraisingOpportunity::create([
        'name' => 'Bando Regione X',
        'deadline' => now()->addMonth()->toDateString(),
        'created_by' => $creator->id,
        'responsible_user_id' => $creator->id,
    ]);

    return FundraisingEvaluationScore::create([
        'fundraising_opportunity_id' => $opportunity->id,
        'criterion_key' => 'criterion_a',
        'score' => 3,
    ]);
}

test('a user without any fundraising.* permission is denied every FundraisingEvaluationScorePolicy ability', function (): void {
    $actor = userWithPermissions();
    $score = makeFundraisingEvaluationScore();

    expect($actor->can('viewAny', FundraisingEvaluationScore::class))->toBeFalse()
        ->and($actor->can('view', $score))->toBeFalse()
        ->and($actor->can('create', FundraisingEvaluationScore::class))->toBeFalse()
        ->and($actor->can('update', $score))->toBeFalse()
        ->and($actor->can('delete', $score))->toBeFalse();
});

test('viewing scores is gated by fundraising.view.*, writing them by fundraising.evaluate', function (): void {
    $score = makeFundraisingEvaluationScore();

    $viewer = userWithPermissions(PermissionEnum::FundraisingViewAny);
    expect($viewer->can('view', $score))->toBeTrue()
        ->and($viewer->can('create', FundraisingEvaluationScore::class))->toBeFalse();

    $evaluator = userWithPermissions(PermissionEnum::FundraisingEvaluate);
    expect($evaluator->can('create', FundraisingEvaluationScore::class))->toBeTrue()
        ->and($evaluator->can('update', $score))->toBeTrue()
        ->and($evaluator->can('delete', $score))->toBeTrue();
});
