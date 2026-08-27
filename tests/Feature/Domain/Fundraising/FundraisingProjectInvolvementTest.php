<?php

declare(strict_types=1);

use App\Domain\Fundraising\Models\FundraisingOpportunity;
use App\Domain\Fundraising\Models\FundraisingProject;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeFundraisingProjectForInvolvementTest(): FundraisingProject
{
    $creator = User::factory()->create();
    $opportunity = FundraisingOpportunity::create([
        'name' => 'Bando Regione X',
        'deadline' => now()->addMonth()->toDateString(),
        'created_by' => $creator->id,
        'responsible_user_id' => $creator->id,
    ]);

    return FundraisingProject::create([
        'title' => 'Progetto rifugio A',
        'fundraising_opportunity_id' => $opportunity->id,
        'created_by' => $creator->id,
    ]);
}

test('scopeInvolving trova il progetto per capofila, partner, responsabile o creatore', function (): void {
    $lead = User::factory()->create();
    $partner = User::factory()->create();
    $responsible = User::factory()->create();
    $creator = User::factory()->create();
    $stranger = User::factory()->create();

    $opportunity = FundraisingOpportunity::create([
        'name' => 'Bando Regione X',
        'deadline' => now()->addMonth()->toDateString(),
        'created_by' => $creator->id,
        'responsible_user_id' => $creator->id,
    ]);

    $project = FundraisingProject::create([
        'title' => 'Progetto rifugio A',
        'fundraising_opportunity_id' => $opportunity->id,
        'lead_user_id' => $lead->id,
        'responsible_user_id' => $responsible->id,
        'created_by' => $creator->id,
    ]);
    $project->partners()->attach($partner->id);

    expect(FundraisingProject::query()->involving($lead)->whereKey($project->id)->exists())->toBeTrue()
        ->and(FundraisingProject::query()->involving($partner)->whereKey($project->id)->exists())->toBeTrue()
        ->and(FundraisingProject::query()->involving($responsible)->whereKey($project->id)->exists())->toBeTrue()
        ->and(FundraisingProject::query()->involving($creator)->whereKey($project->id)->exists())->toBeTrue()
        ->and(FundraisingProject::query()->involving($stranger)->whereKey($project->id)->exists())->toBeFalse();
});

test('partnerCustomers restituisce solo i partner con ruolo customer', function (): void {
    $project = makeFundraisingProjectForInvolvementTest();

    $customerPartner = withRole(User::factory()->create(), UserRole::Customer);
    $staffPartner = withRole(User::factory()->create(), UserRole::Fundraising);

    $project->partners()->attach([$customerPartner->id, $staffPartner->id]);

    $result = $project->partnerCustomers()->get();

    expect($result->pluck('id')->all())->toBe([$customerPartner->id]);
});
