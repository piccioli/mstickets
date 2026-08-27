<?php

declare(strict_types=1);

use App\Domain\Fundraising\Enums\FundraisingEvaluationCriterion;
use App\Domain\Fundraising\Models\FundraisingEvaluationScore;
use App\Domain\Fundraising\Models\FundraisingOpportunity;
use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Models\User;
use App\Filament\Resources\FundraisingOpportunities\Pages\EditFundraisingOpportunity;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

function makeOpportunityForEvaluationGrid(): FundraisingOpportunity
{
    $user = User::factory()->create();

    return FundraisingOpportunity::create([
        'name' => 'Bando test griglia',
        'deadline' => today()->addMonth()->toDateString(),
        'created_by' => $user->id,
        'responsible_user_id' => $user->id,
    ]);
}

test('compilare la griglia dalla pagina Edit persiste i punteggi e aggiorna i totali coerentemente col service', function (): void {
    $opportunity = makeOpportunityForEvaluationGrid();
    $evaluator = userWithPermissions(PermissionEnum::FundraisingViewAny, PermissionEnum::FundraisingUpdate, PermissionEnum::FundraisingEvaluate);
    $this->actingAs($evaluator);

    Livewire::test(EditFundraisingOpportunity::class, ['record' => $opportunity->getKey()])
        ->fillForm([
            'scores' => [
                FundraisingEvaluationCriterion::CriterionA->value => 4,
                FundraisingEvaluationCriterion::RiskFinanziari->value => -2,
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $fresh = $opportunity->fresh();

    expect(FundraisingEvaluationScore::query()->count())->toBe(2)
        ->and($fresh->evaluation_positive_total)->toBe(4)
        ->and($fresh->evaluation_negative_total)->toBe(2)
        ->and($fresh->evaluation_total)->toBe(2);
});

test('evaluated_by/evaluated_at si valorizzano al primo salvataggio e restano invariati ai successivi', function (): void {
    $opportunity = makeOpportunityForEvaluationGrid();
    $evaluator = userWithPermissions(PermissionEnum::FundraisingViewAny, PermissionEnum::FundraisingUpdate, PermissionEnum::FundraisingEvaluate);
    $this->actingAs($evaluator);

    Livewire::test(EditFundraisingOpportunity::class, ['record' => $opportunity->getKey()])
        ->fillForm(['scores' => [FundraisingEvaluationCriterion::CriterionA->value => 3]])
        ->call('save')
        ->assertHasNoFormErrors();

    $firstEvaluatedAt = $opportunity->fresh()->evaluated_at;
    expect($opportunity->fresh()->evaluated_by)->toBe($evaluator->id)
        ->and($firstEvaluatedAt)->not->toBeNull();

    $otherEvaluator = userWithPermissions(PermissionEnum::FundraisingViewAny, PermissionEnum::FundraisingUpdate, PermissionEnum::FundraisingEvaluate);
    $this->actingAs($otherEvaluator);

    test()->travel(1)->hours();

    Livewire::test(EditFundraisingOpportunity::class, ['record' => $opportunity->getKey()])
        ->fillForm(['scores' => [FundraisingEvaluationCriterion::CriterionB->value => 5]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($opportunity->fresh()->evaluated_by)->toBe($evaluator->id)
        ->and($opportunity->fresh()->evaluated_at->equalTo($firstEvaluatedAt))->toBeTrue();
});

test('un punteggio fuori dal range del criterio produce un errore di validazione leggibile', function (): void {
    $opportunity = makeOpportunityForEvaluationGrid();
    $evaluator = userWithPermissions(PermissionEnum::FundraisingViewAny, PermissionEnum::FundraisingUpdate, PermissionEnum::FundraisingEvaluate);
    $this->actingAs($evaluator);

    Livewire::test(EditFundraisingOpportunity::class, ['record' => $opportunity->getKey()])
        ->fillForm(['scores' => [FundraisingEvaluationCriterion::CriterionA->value => 9]])
        ->call('save')
        ->assertHasFormErrors(['scores.criterion_a']);

    expect(FundraisingEvaluationScore::query()->count())->toBe(0);
});

test('il tab Valutazione non è visibile a chi ha solo fundraising.update, senza fundraising.evaluate', function (): void {
    $opportunity = makeOpportunityForEvaluationGrid();
    $editor = userWithPermissions(PermissionEnum::FundraisingViewAny, PermissionEnum::FundraisingUpdate);
    $this->actingAs($editor);

    Livewire::test(EditFundraisingOpportunity::class, ['record' => $opportunity->getKey()])
        ->fillForm(['scores' => [FundraisingEvaluationCriterion::CriterionA->value => 4]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(FundraisingEvaluationScore::query()->count())->toBe(0);
});

test('la griglia riprende i punteggi già persistiti quando si riapre la pagina Edit', function (): void {
    $opportunity = makeOpportunityForEvaluationGrid();
    $evaluator = userWithPermissions(PermissionEnum::FundraisingViewAny, PermissionEnum::FundraisingUpdate, PermissionEnum::FundraisingEvaluate);
    $this->actingAs($evaluator);

    FundraisingEvaluationScore::query()->create([
        'fundraising_opportunity_id' => $opportunity->id,
        'criterion_key' => FundraisingEvaluationCriterion::CriterionA,
        'score' => 5,
        'notes' => null,
    ]);

    Livewire::test(EditFundraisingOpportunity::class, ['record' => $opportunity->getKey()])
        ->assertFormSet(['scores.'.FundraisingEvaluationCriterion::CriterionA->value => 5]);
});
