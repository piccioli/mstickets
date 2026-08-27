<?php

declare(strict_types=1);

use App\Domain\Fundraising\Models\FundraisingOpportunity;
use App\Domain\Fundraising\Models\FundraisingProject;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Resources\FundraisingProjects\Pages\EditFundraisingProject;
use App\Filament\Resources\FundraisingProjects\RelationManagers\PartnersRelationManager;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

function makeFundraisingProjectForRelationManagerTest(): FundraisingProject
{
    $user = User::factory()->create();

    $opportunity = FundraisingOpportunity::create([
        'name' => 'Bando test',
        'deadline' => today()->addMonth()->toDateString(),
        'created_by' => $user->id,
        'responsible_user_id' => $user->id,
    ]);

    return FundraisingProject::create([
        'title' => 'Progetto test',
        'fundraising_opportunity_id' => $opportunity->id,
        'created_by' => $user->id,
    ]);
}

test('un utente fundraising può aggiungere e rimuovere un partner dal progetto', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(withRole(User::factory()->create(), UserRole::Fundraising));

    $project = makeFundraisingProjectForRelationManagerTest();
    $partner = User::factory()->create();

    // `callTableAction(..., data: [...])` passa dal medesimo helper di test
    // `fillForm()` che in questo ambiente non applica correttamente lo stato
    // (bug pre-esistente, vedi CLAUDE.md): `mountTableAction()` + `->set(...)` +
    // `callMountedTableAction()` applica lo stato correttamente.
    Livewire::test(PartnersRelationManager::class, [
        'ownerRecord' => $project,
        'pageClass' => EditFundraisingProject::class,
    ])
        ->mountTableAction('attach')
        ->set('mountedActions.0.data.recordId', $partner->id)
        ->callMountedTableAction()
        ->assertHasNoTableActionErrors();

    expect($project->partners()->pluck('users.id')->all())->toContain($partner->id);

    Livewire::test(PartnersRelationManager::class, [
        'ownerRecord' => $project,
        'pageClass' => EditFundraisingProject::class,
    ])
        ->callTableAction('detach', record: $partner)
        ->assertHasNoTableActionErrors();

    expect($project->partners()->pluck('users.id')->all())->not->toContain($partner->id);
});
