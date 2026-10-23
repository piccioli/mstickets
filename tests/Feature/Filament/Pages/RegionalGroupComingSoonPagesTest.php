<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\RegionalGroupMembers;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    $this->seed(RolePermissionSeeder::class);
});

dataset('regional group coming soon pages', [
    'Soci' => [RegionalGroupMembers::class, 'Soci', '/gr/soci'],
]);

function regionalGroupComingSoonCustomer(?CustomerType $type): User
{
    $customer = withRole(User::factory()->create(), UserRole::Customer);
    $customer->forceFill(['customer_type' => $type])->save();

    return $customer;
}

test('a regional group customer sees the coming soon page', function (string $page, string $title, string $slug): void {
    expect($page::getUrl())->toEndWith($slug);

    $this->actingAs(regionalGroupComingSoonCustomer(CustomerType::GruppoRegionale))
        ->get($page::getUrl())
        ->assertSuccessful()
        ->assertSee($title)
        ->assertSee('Questa sezione è in arrivo.')
        ->assertSee('Torna alla dashboard')
        ->assertSee(Dashboard::getUrl(), escape: false);
})->with('regional group coming soon pages');

test('a staff user cannot access the regional group coming soon page', function (string $page): void {
    $this->actingAs(withRole(User::factory()->create(), UserRole::Developer))
        ->get($page::getUrl())
        ->assertForbidden();
})->with('regional group coming soon pages');

test('a customer of another type cannot access the regional group coming soon page', function (string $page, string $title, string $slug, ?CustomerType $type): void {
    $this->actingAs(regionalGroupComingSoonCustomer($type))
        ->get($page::getUrl())
        ->assertForbidden();
})->with('regional group coming soon pages')->with([
    'Sezione' => [CustomerType::Sezione],
    'Organo Tecnico' => [CustomerType::OrganoTecnicoStrutturaOperativa],
    'nessun tipo' => [null],
]);

test('the regional group coming soon pages are not in the navigation', function (string $page): void {
    expect($page::shouldRegisterNavigation())->toBeFalse();
})->with('regional group coming soon pages');
