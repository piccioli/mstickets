<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Filament\Pages\CustomerRegionalSectionsDashboard;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

test('a gruppo regionale customer can access the page', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $groupLeader = withRole(User::factory()->create(), UserRole::Customer);
    $groupLeader->forceFill(['customer_type' => CustomerType::GruppoRegionale])->save();

    $this->actingAs($groupLeader)->get(CustomerRegionalSectionsDashboard::getUrl())->assertSuccessful();
});

test('a sezione customer cannot access the page', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $customer->forceFill(['customer_type' => CustomerType::Sezione])->save();

    $this->actingAs($customer)->get(CustomerRegionalSectionsDashboard::getUrl())->assertForbidden();
});

test('a non-customer cannot access the page', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $developer = withRole(User::factory()->create(), UserRole::Developer);

    $this->actingAs($developer)->get(CustomerRegionalSectionsDashboard::getUrl())->assertForbidden();
});

test('the navigation group is "Sezioni"', function (): void {
    expect(CustomerRegionalSectionsDashboard::getNavigationGroup())->toBe('Sezioni');
});

test('it lists only sections in the same region, with their open ticket count', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $groupLeader = withRole(User::factory()->create(), UserRole::Customer);
    $groupLeader->forceFill(['customer_type' => CustomerType::GruppoRegionale, 'region' => Region::Lombardia])->save();

    $sameRegionSection = withRole(User::factory()->create(['name' => 'Sezione di Milano']), UserRole::Customer);
    $sameRegionSection->forceFill(['customer_type' => CustomerType::Sezione, 'region' => Region::Lombardia])->save();
    ticket(['requester_id' => $sameRegionSection->id, 'status' => TicketStatus::Todo]);
    ticket(['requester_id' => $sameRegionSection->id, 'status' => TicketStatus::Done]);

    $otherRegionSection = withRole(User::factory()->create(['name' => 'Sezione di Roma']), UserRole::Customer);
    $otherRegionSection->forceFill(['customer_type' => CustomerType::Sezione, 'region' => Region::Lazio])->save();

    $this->actingAs($groupLeader);

    $sections = Livewire::test(CustomerRegionalSectionsDashboard::class)->instance()->regionalGroupSections();

    expect($sections->pluck('id'))->toContain($sameRegionSection->id)
        ->not->toContain($otherRegionSection->id);

    $this->get(CustomerRegionalSectionsDashboard::getUrl())
        ->assertSee('Sezioni del gruppo regionale')
        ->assertSee('Sezione di Milano')
        ->assertSee('1 ticket aperti')
        ->assertDontSee('Sezione di Roma');
});

test('it shows an explicit empty state when the region has no sections yet', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $groupLeader = withRole(User::factory()->create(), UserRole::Customer);
    $groupLeader->forceFill(['customer_type' => CustomerType::GruppoRegionale, 'region' => Region::Molise])->save();

    $this->actingAs($groupLeader)
        ->get(CustomerRegionalSectionsDashboard::getUrl())
        ->assertSee('Sezioni del gruppo regionale')
        ->assertSee('Nessuna sezione classificata in questa regione');
});

test('it shows an explicit empty state when the group has no region', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $groupLeader = withRole(User::factory()->create(), UserRole::Customer);
    $groupLeader->forceFill(['customer_type' => CustomerType::GruppoRegionale, 'region' => null])->save();

    $this->actingAs($groupLeader)
        ->get(CustomerRegionalSectionsDashboard::getUrl())
        ->assertSee('Sezioni del gruppo regionale')
        ->assertSee('Nessuna sezione classificata in questa regione');
});
