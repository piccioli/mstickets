<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\SectionFinancialStatements;
use App\Filament\Pages\SectionMembers;
use App\Filament\Pages\SectionSicaiStages;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    $this->seed(RolePermissionSeeder::class);
});

dataset('section coming soon pages', [
    'Soci' => [SectionMembers::class, 'Soci'],
    'Bilancio' => [SectionFinancialStatements::class, 'Bilancio'],
    'Tappe SICAI' => [SectionSicaiStages::class, 'Tappe SICAI'],
]);

function comingSoonCustomer(?CustomerType $type): User
{
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $customer->forceFill(['customer_type' => $type])->save();

    return $customer;
}

test('a sezione customer sees the coming soon page', function (string $page, string $title): void {
    $this->actingAs(comingSoonCustomer(CustomerType::Sezione))
        ->get($page::getUrl())
        ->assertSuccessful()
        ->assertSee($title)
        ->assertSee('Questa sezione è in arrivo.')
        ->assertSee('Torna alla dashboard')
        ->assertSee(Dashboard::getUrl(), escape: false);
})->with('section coming soon pages');

test('a staff user cannot access the coming soon page', function (string $page): void {
    $this->actingAs(withRole(User::factory()->create(), UserRole::Developer))
        ->get($page::getUrl())
        ->assertForbidden();
})->with('section coming soon pages');

test('a customer of another type cannot access the coming soon page', function (string $page, string $title, ?CustomerType $type): void {
    $this->actingAs(comingSoonCustomer($type))
        ->get($page::getUrl())
        ->assertForbidden();
})->with('section coming soon pages')->with([
    'Gruppo Regionale' => [CustomerType::GruppoRegionale],
    'Organo Tecnico' => [CustomerType::OrganoTecnicoStrutturaOperativa],
    'nessun tipo' => [null],
]);

test('the coming soon pages are not in the navigation', function (string $page): void {
    expect($page::shouldRegisterNavigation())->toBeFalse();
})->with('section coming soon pages');
