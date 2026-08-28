<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Pages\CaiSectionRegionalDetail;
use App\Filament\Pages\CustomerDashboard;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

function gruppoRegionale(Region $region): User
{
    $user = withRole(User::factory()->create(), UserRole::Customer);
    $user->forceFill(['customer_type' => CustomerType::GruppoRegionale, 'region' => $region])->save();

    return $user;
}

function sezione(Region $region, string $name = 'Sezione di test'): User
{
    $user = withRole(User::factory()->create(['name' => $name]), UserRole::Customer);
    $user->forceFill(['customer_type' => CustomerType::Sezione, 'region' => $region])->save();

    return $user;
}

test('a gruppo regionale customer can open the detail of a section in their own region', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $groupLeader = gruppoRegionale(Region::Lombardia);
    $section = sezione(Region::Lombardia, 'Sezione di Milano');

    $this->actingAs($groupLeader)
        ->get(CaiSectionRegionalDetail::getUrl(['record' => $section->id]))
        ->assertSuccessful()
        ->assertSee('Sezione di Milano');
});

test('a direct attempt to open a section of another region is forbidden', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $groupLeader = gruppoRegionale(Region::Lombardia);
    $otherRegionSection = sezione(Region::Lazio, 'Sezione di Roma');

    $this->actingAs($groupLeader)
        ->get(CaiSectionRegionalDetail::getUrl(['record' => $otherRegionSection->id]))
        ->assertForbidden();
});

test('a gruppo regionale customer without a region cannot open any section detail', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $groupLeader = gruppoRegionale(Region::Lombardia);
    $groupLeader->forceFill(['region' => null])->save();
    $section = sezione(Region::Lombardia);

    $this->actingAs($groupLeader)
        ->get(CaiSectionRegionalDetail::getUrl(['record' => $section->id]))
        ->assertForbidden();
});

test('a sezione customer cannot access the regional group detail page', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $sezioneCustomer = sezione(Region::Lombardia);
    $otherSection = sezione(Region::Lombardia, 'Altra sezione');

    $this->actingAs($sezioneCustomer)
        ->get(CaiSectionRegionalDetail::getUrl(['record' => $otherSection->id]))
        ->assertForbidden();
});

test('a non-customer cannot access the regional group detail page', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $developer = withRole(User::factory()->create(), UserRole::Developer);
    $section = sezione(Region::Lombardia);

    $this->actingAs($developer)
        ->get(CaiSectionRegionalDetail::getUrl(['record' => $section->id]))
        ->assertForbidden();
});

test('opening the detail for a user that is not a sezione is not found', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $groupLeader = gruppoRegionale(Region::Lombardia);
    $notASection = User::factory()->create();

    $this->actingAs($groupLeader)
        ->get(CaiSectionRegionalDetail::getUrl(['record' => $notASection->id]))
        ->assertNotFound();
});

test('the detail page shows the same cai section data as the customer own dashboard, reusing the same infolist', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $groupLeader = gruppoRegionale(Region::Lombardia);
    $section = sezione(Region::Lombardia, 'Sezione di Bergamo');

    CaiSection::create([
        'codice_cai' => 'CAI-100',
        'name' => 'Sezione di Bergamo',
        'region' => 'LOMBARDIA',
        'founded_year' => 1950,
        'user_id' => $section->id,
    ]);

    $this->actingAs($groupLeader)
        ->get(CaiSectionRegionalDetail::getUrl(['record' => $section->id]))
        ->assertSuccessful()
        ->assertSee('Sezione di Bergamo')
        ->assertSee('1950')
        ->assertDontSee('Nessun dato CAI/RUNTS disponibile per questa sezione');
});

test('the detail page shows an explicit empty state for a section without linked cai data', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $groupLeader = gruppoRegionale(Region::Lombardia);
    $section = sezione(Region::Lombardia, 'Sezione senza dati CAI');

    $this->actingAs($groupLeader)
        ->get(CaiSectionRegionalDetail::getUrl(['record' => $section->id]))
        ->assertSuccessful()
        ->assertSee('Nessun dato CAI/RUNTS disponibile per questa sezione');
});

test('the regional group sections card on the customer dashboard links to the section detail page', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $groupLeader = gruppoRegionale(Region::Lombardia);
    $section = sezione(Region::Lombardia, 'Sezione di Como');

    $this->actingAs($groupLeader)
        ->get(CustomerDashboard::getUrl())
        ->assertSee(CaiSectionRegionalDetail::getUrl(['record' => $section->id]), false);
});
