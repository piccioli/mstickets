<?php

declare(strict_types=1);

use App\Domain\Fundraising\Enums\FundraisingProjectStatus;
use App\Domain\Fundraising\Models\FundraisingOpportunity;
use App\Domain\Fundraising\Models\FundraisingProject;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Resources\FundraisingProjects\FundraisingProjectResource;
use App\Filament\Resources\FundraisingProjects\Pages\CreateFundraisingProject;
use App\Filament\Resources\FundraisingProjects\Pages\ListFundraisingProjects;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

/**
 * @param  array<string, mixed>  $attributes
 */
function makeFundraisingProjectForResourceTest(array $attributes = []): FundraisingProject
{
    $user = User::factory()->create();

    $opportunity = FundraisingOpportunity::create([
        'name' => 'Bando test',
        'deadline' => today()->addMonth()->toDateString(),
        'created_by' => $user->id,
        'responsible_user_id' => $user->id,
    ]);

    return FundraisingProject::create(array_merge([
        'title' => 'Progetto test',
        'fundraising_opportunity_id' => $opportunity->id,
        'created_by' => $user->id,
    ], $attributes));
}

test('FundraisingProjectResource visibility per ruolo (§9.4, mai manager/developer/customer)', function (UserRole $role, bool $visible): void {
    $this->seed(RolePermissionSeeder::class);

    $user = withRole(User::factory()->create(), $role);

    $this->actingAs($user);

    expect(FundraisingProjectResource::canViewAny())->toBe($visible);

    $response = $this->get(FundraisingProjectResource::getUrl('index'));

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

test('filtro stato produce il sottoinsieme atteso', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(withRole(User::factory()->create(), UserRole::Fundraising));

    $draft = makeFundraisingProjectForResourceTest(['title' => 'Bozza', 'status' => FundraisingProjectStatus::Draft]);
    $submitted = makeFundraisingProjectForResourceTest(['title' => 'Presentato', 'status' => FundraisingProjectStatus::Submitted]);

    Livewire::test(ListFundraisingProjects::class)
        ->filterTable('status', FundraisingProjectStatus::Submitted->value)
        ->assertCanSeeTableRecords([$submitted])
        ->assertCanNotSeeTableRecords([$draft]);
});

test('filtro capofila produce il sottoinsieme atteso', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(withRole(User::factory()->create(), UserRole::Fundraising));

    $lead = User::factory()->create();
    $withLead = makeFundraisingProjectForResourceTest(['title' => 'Con capofila', 'lead_user_id' => $lead->id]);
    $withoutLead = makeFundraisingProjectForResourceTest(['title' => 'Senza capofila']);

    Livewire::test(ListFundraisingProjects::class)
        ->filterTable('lead_user_id', $lead->id)
        ->assertCanSeeTableRecords([$withLead])
        ->assertCanNotSeeTableRecords([$withoutLead]);
});

test('filtro partner produce il sottoinsieme atteso', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(withRole(User::factory()->create(), UserRole::Fundraising));

    $partner = User::factory()->create();
    $withPartner = makeFundraisingProjectForResourceTest(['title' => 'Con partner']);
    $withPartner->partners()->attach($partner->id);
    $withoutPartner = makeFundraisingProjectForResourceTest(['title' => 'Senza partner']);

    Livewire::test(ListFundraisingProjects::class)
        ->filterTable('partner', $partner->id)
        ->assertCanSeeTableRecords([$withPartner])
        ->assertCanNotSeeTableRecords([$withoutPartner]);
});

test('filtro coinvolti produce il sottoinsieme atteso (capofila OR partner OR responsabile OR creatore)', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $staff = withRole(User::factory()->create(), UserRole::Fundraising);
    $this->actingAs($staff);

    $involving = makeFundraisingProjectForResourceTest(['title' => 'Coinvolto', 'lead_user_id' => $staff->id]);
    $notInvolving = makeFundraisingProjectForResourceTest(['title' => 'Non coinvolto']);

    Livewire::test(ListFundraisingProjects::class)
        ->filterTable('involving', true)
        ->assertCanSeeTableRecords([$involving])
        ->assertCanNotSeeTableRecords([$notInvolving]);
});

test('creare un progetto valorizza created_by con l\'utente autenticato', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $creator = withRole(User::factory()->create(), UserRole::Fundraising);
    $opportunity = FundraisingOpportunity::create([
        'name' => 'Bando per il nuovo progetto',
        'deadline' => today()->addMonth()->toDateString(),
        'created_by' => $creator->id,
        'responsible_user_id' => $creator->id,
    ]);

    $this->actingAs($creator);

    // `fillForm()` non applica correttamente lo stato su questo form in questo
    // ambiente di test (bug pre-esistente già osservato su
    // `FundraisingOpportunityResourceTest`/`TicketResourceTest`, mai investigato
    // perché confinato al solo helper di test `fillFormDataForTesting` — vedi
    // CLAUDE.md): `->set('data.<campo>', ...)` applica lo stato correttamente ed
    // esercita comunque `handleRecordCreation()` con dati reali.
    Livewire::test(CreateFundraisingProject::class)
        ->set('data.title', 'Progetto nuovo')
        ->set('data.fundraising_opportunity_id', $opportunity->id)
        ->call('create')
        ->assertHasNoFormErrors();

    $project = FundraisingProject::query()->where('title', 'Progetto nuovo')->firstOrFail();

    expect($project->created_by)->toBe($creator->id)
        ->and($project->status)->toBe(FundraisingProjectStatus::Draft);
});
