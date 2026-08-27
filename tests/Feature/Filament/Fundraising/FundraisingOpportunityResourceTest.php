<?php

declare(strict_types=1);

use App\Domain\Fundraising\Enums\TerritorialScope;
use App\Domain\Fundraising\Models\FundraisingOpportunity;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Resources\FundraisingOpportunities\FundraisingOpportunityResource;
use App\Filament\Resources\FundraisingOpportunities\Pages\CreateFundraisingOpportunity;
use App\Filament\Resources\FundraisingOpportunities\Pages\EditFundraisingOpportunity;
use App\Filament\Resources\FundraisingOpportunities\Pages\ListFundraisingOpportunities;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

function makeFundraisingOpportunityForResourceTest(array $attributes = []): FundraisingOpportunity
{
    $user = User::factory()->create();

    return FundraisingOpportunity::create(array_merge([
        'name' => 'Bando test',
        'deadline' => today()->addMonth()->toDateString(),
        'territorial_scope' => TerritorialScope::National,
        'created_by' => $user->id,
        'responsible_user_id' => $user->id,
    ], $attributes));
}

test('FundraisingOpportunityResource visibility per ruolo (§9.4, mai manager/developer/customer)', function (UserRole $role, bool $visible): void {
    $this->seed(RolePermissionSeeder::class);

    $user = withRole(User::factory()->create(), $role);

    $this->actingAs($user);

    expect(FundraisingOpportunityResource::canViewAny())->toBe($visible);

    $response = $this->get(FundraisingOpportunityResource::getUrl('index'));

    if ($visible) {
        $response->assertOk();
    } else {
        $response->assertForbidden();
    }
})->with([
    'admin — visibile' => [UserRole::Admin, true],
    'fundraising — visibile' => [UserRole::Fundraising, true],
    'manager — mai visibile' => [UserRole::Manager, false],
    'developer — mai visibile' => [UserRole::Developer, false],
    'customer — mai visibile (ha solo view.involved, la vista cliente è separata)' => [UserRole::Customer, false],
]);

test('elenco mostra solo le opportunità attive di default, archivio mostra solo le scadute', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(withRole(User::factory()->create(), UserRole::Fundraising));

    $active = makeFundraisingOpportunityForResourceTest(['name' => 'Attiva', 'deadline' => today()->addWeek()->toDateString()]);
    $expired = makeFundraisingOpportunityForResourceTest(['name' => 'Scaduta', 'deadline' => today()->subWeek()->toDateString()]);

    Livewire::test(ListFundraisingOpportunities::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$active])
        ->assertCanNotSeeTableRecords([$expired]);

    Livewire::test(ListFundraisingOpportunities::class)
        ->set('activeTab', 'archive')
        ->assertCanSeeTableRecords([$expired])
        ->assertCanNotSeeTableRecords([$active]);
});

test('filtro ambito territoriale produce il sottoinsieme atteso', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(withRole(User::factory()->create(), UserRole::Fundraising));

    $regional = makeFundraisingOpportunityForResourceTest(['name' => 'Regionale', 'territorial_scope' => TerritorialScope::Regional]);
    $national = makeFundraisingOpportunityForResourceTest(['name' => 'Nazionale', 'territorial_scope' => TerritorialScope::National]);

    Livewire::test(ListFundraisingOpportunities::class)
        ->filterTable('territorial_scope', TerritorialScope::Regional->value)
        ->assertCanSeeTableRecords([$regional])
        ->assertCanNotSeeTableRecords([$national]);
});

test('filtro cofinanziamento con/senza quota produce il sottoinsieme atteso', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(withRole(User::factory()->create(), UserRole::Fundraising));

    $withQuota = makeFundraisingOpportunityForResourceTest(['name' => 'Con quota', 'cofinancing_quota' => 20]);
    $withoutQuota = makeFundraisingOpportunityForResourceTest(['name' => 'Senza quota', 'cofinancing_quota' => null]);

    Livewire::test(ListFundraisingOpportunities::class)
        ->filterTable('cofinancing_quota', true)
        ->assertCanSeeTableRecords([$withQuota])
        ->assertCanNotSeeTableRecords([$withoutQuota]);

    Livewire::test(ListFundraisingOpportunities::class)
        ->filterTable('cofinancing_quota', false)
        ->assertCanSeeTableRecords([$withoutQuota])
        ->assertCanNotSeeTableRecords([$withQuota]);
});

test('filtro scaduto/attivo produce il sottoinsieme atteso', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(withRole(User::factory()->create(), UserRole::Fundraising));

    $active = makeFundraisingOpportunityForResourceTest(['name' => 'Attiva', 'deadline' => today()->addWeek()->toDateString()]);
    $expired = makeFundraisingOpportunityForResourceTest(['name' => 'Scaduta', 'deadline' => today()->subWeek()->toDateString()]);

    Livewire::test(ListFundraisingOpportunities::class)
        ->set('activeTab', 'archive')
        ->filterTable('expired', true)
        ->assertCanSeeTableRecords([$expired])
        ->assertCanNotSeeTableRecords([$active]);
});

test('creare un\'opportunità valorizza created_by con l\'utente autenticato', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $creator = withRole(User::factory()->create(), UserRole::Fundraising);
    $responsible = User::factory()->create();

    $this->actingAs($creator);

    Livewire::test(CreateFundraisingOpportunity::class)
        ->fillForm([
            'name' => 'Bando nuovo',
            'deadline' => today()->addMonth()->toDateString(),
            'territorial_scope' => TerritorialScope::National->value,
            'responsible_user_id' => $responsible->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $opportunity = FundraisingOpportunity::query()->where('name', 'Bando nuovo')->firstOrFail();

    expect($opportunity->created_by)->toBe($creator->id)
        ->and($opportunity->responsible_user_id)->toBe($responsible->id);
});

test('modificare un\'opportunità non altera mai created_by', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $editor = withRole(User::factory()->create(), UserRole::Fundraising);
    $opportunity = makeFundraisingOpportunityForResourceTest(['name' => 'Bando esistente']);
    $originalCreatedBy = $opportunity->created_by;

    $this->actingAs($editor);

    Livewire::test(EditFundraisingOpportunity::class, ['record' => $opportunity->getKey()])
        ->fillForm(['name' => 'Bando rinominato'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($opportunity->fresh()->name)->toBe('Bando rinominato')
        ->and($opportunity->fresh()->created_by)->toBe($originalCreatedBy);
});
