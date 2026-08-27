<?php

declare(strict_types=1);

use App\Domain\Fundraising\Models\FundraisingOpportunity;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Resources\CustomerFundraisingOpportunities\CustomerFundraisingOpportunityResource;
use App\Filament\Resources\CustomerFundraisingOpportunities\Pages\ListCustomerFundraisingOpportunities;
use App\Filament\Resources\FundraisingOpportunities\FundraisingOpportunityResource;
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
function makeFundraisingOpportunityForCustomerViewTest(array $attributes = []): FundraisingOpportunity
{
    $staff = User::factory()->create();

    return FundraisingOpportunity::create(array_merge([
        'name' => 'Bando test',
        'deadline' => today()->addMonth()->toDateString(),
        'created_by' => $staff->id,
        'responsible_user_id' => $staff->id,
    ], $attributes));
}

test('CustomerFundraisingOpportunityResource visibility per ruolo (§6.6.4, SOLO customer)', function (UserRole $role, bool $visible): void {
    $this->seed(RolePermissionSeeder::class);

    $user = withRole(User::factory()->create(), $role);

    $this->actingAs($user);

    expect(CustomerFundraisingOpportunityResource::canViewAny())->toBe($visible);

    $response = $this->get(CustomerFundraisingOpportunityResource::getUrl('index'));

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

test('qualunque customer autenticato vede qualunque opportunità nell\'elenco, nessuna differenza attive/scadute', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $this->actingAs($customer);

    $active = makeFundraisingOpportunityForCustomerViewTest(['name' => 'Attiva', 'deadline' => today()->addMonth()->toDateString()]);
    $expired = makeFundraisingOpportunityForCustomerViewTest(['name' => 'Scaduta', 'deadline' => today()->subMonth()->toDateString()]);

    Livewire::test(ListCustomerFundraisingOpportunities::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$active, $expired]);
});

test('il dettaglio di un\'opportunità è raggiungibile da un customer, in sola lettura', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $this->actingAs($customer);

    $opportunity = makeFundraisingOpportunityForCustomerViewTest(['name' => 'Bando dettaglio']);

    $this->get(CustomerFundraisingOpportunityResource::getUrl('view', ['record' => $opportunity]))
        ->assertOk()
        ->assertSee('Bando dettaglio');
});

test('CustomerFundraisingOpportunityResource non registra pagine di scrittura', function (): void {
    expect(array_keys(CustomerFundraisingOpportunityResource::getPages()))->toBe(['index', 'view']);
});

test('la Resource staff resta invisibile a un customer (US-502, invariato da questa story)', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $this->actingAs($customer);

    expect(FundraisingOpportunityResource::canViewAny())->toBeFalse();
});
