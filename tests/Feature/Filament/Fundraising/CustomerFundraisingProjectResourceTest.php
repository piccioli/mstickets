<?php

declare(strict_types=1);

use App\Domain\Fundraising\Models\FundraisingOpportunity;
use App\Domain\Fundraising\Models\FundraisingProject;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Resources\CustomerFundraisingProjects\CustomerFundraisingProjectResource;
use App\Filament\Resources\CustomerFundraisingProjects\Pages\ListCustomerFundraisingProjects;
use App\Filament\Resources\FundraisingProjects\FundraisingProjectResource;
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
function makeFundraisingProjectForCustomerViewTest(array $attributes = []): FundraisingProject
{
    $staff = User::factory()->create();

    $opportunity = FundraisingOpportunity::create([
        'name' => 'Bando test',
        'deadline' => today()->addMonth()->toDateString(),
        'created_by' => $staff->id,
        'responsible_user_id' => $staff->id,
    ]);

    return FundraisingProject::create(array_merge([
        'title' => 'Progetto test',
        'fundraising_opportunity_id' => $opportunity->id,
        'created_by' => $staff->id,
    ], $attributes));
}

test('CustomerFundraisingProjectResource visibility per ruolo (§6.6.4, SOLO customer)', function (UserRole $role, bool $visible): void {
    $this->seed(RolePermissionSeeder::class);

    $user = withRole(User::factory()->create(), $role);

    $this->actingAs($user);

    expect(CustomerFundraisingProjectResource::canViewAny())->toBe($visible);

    $response = $this->get(CustomerFundraisingProjectResource::getUrl('index'));

    if ($visible) {
        $response->assertOk();
    } else {
        $response->assertForbidden();
    }
})->with([
    'admin — mai visibile (usa la Resource staff)' => [UserRole::Admin, false],
    'fundraising — mai visibile (usa la Resource staff)' => [UserRole::Fundraising, false],
    'manager — mai visibile (nessun permesso fundraising)' => [UserRole::Manager, false],
    'developer — mai visibile (nessun permesso fundraising)' => [UserRole::Developer, false],
    'customer — visibile' => [UserRole::Customer, true],
]);

test('un customer capofila vede il proprio progetto nell\'elenco, uno non coinvolto no', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $this->actingAs($customer);

    $ledByMe = makeFundraisingProjectForCustomerViewTest(['title' => 'Il mio progetto', 'lead_user_id' => $customer->id]);
    $unrelated = makeFundraisingProjectForCustomerViewTest(['title' => 'Non mio']);

    Livewire::test(ListCustomerFundraisingProjects::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$ledByMe])
        ->assertCanNotSeeTableRecords([$unrelated]);
});

test('un customer partner vede il progetto nell\'elenco', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $this->actingAs($customer);

    $project = makeFundraisingProjectForCustomerViewTest(['title' => 'Progetto con partner']);
    $project->partners()->attach($customer->id);

    Livewire::test(ListCustomerFundraisingProjects::class)
        ->assertCanSeeTableRecords([$project]);
});

test('responsabile/creatore da soli NON bastano a far vedere il progetto a un customer (§6.6.4)', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $this->actingAs($customer);

    $asResponsible = makeFundraisingProjectForCustomerViewTest(['title' => 'Responsabile', 'responsible_user_id' => $customer->id]);
    $asCreator = makeFundraisingProjectForCustomerViewTest(['title' => 'Creatore', 'created_by' => $customer->id]);

    Livewire::test(ListCustomerFundraisingProjects::class)
        ->assertCanNotSeeTableRecords([$asResponsible, $asCreator]);
});

test('il dettaglio è raggiungibile da un customer coinvolto', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $this->actingAs($customer);

    $project = makeFundraisingProjectForCustomerViewTest(['title' => 'Progetto dettaglio', 'lead_user_id' => $customer->id]);

    $this->get(CustomerFundraisingProjectResource::getUrl('view', ['record' => $project]))
        ->assertOk()
        ->assertSee('Progetto dettaglio');
});

test('il dettaglio di un progetto in cui il customer non è coinvolto non è raggiungibile via URL diretto', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $otherCustomer = withRole(User::factory()->create(), UserRole::Customer);
    $this->actingAs($customer);

    $project = makeFundraisingProjectForCustomerViewTest(['title' => 'Non mio']);
    $project->partners()->attach($otherCustomer->id);

    $this->get(CustomerFundraisingProjectResource::getUrl('view', ['record' => $project]))
        ->assertNotFound();

    expect(FundraisingProject::query()->involvingAsCustomer($customer)->whereKey($project->id)->exists())->toBeFalse();
});

test('CustomerFundraisingProjectResource non registra pagine di scrittura', function (): void {
    expect(array_keys(CustomerFundraisingProjectResource::getPages()))->toBe(['index', 'view']);
});

test('la Resource staff resta invisibile a un customer (US-507, invariato da questa story)', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $this->actingAs($customer);

    expect(FundraisingProjectResource::canViewAny())->toBeFalse();
});
