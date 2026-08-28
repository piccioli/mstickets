<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Pages\CaiSectionsMap;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

function grantCaiDirectoryMapAccess(User $user): User
{
    Role::query()->firstOrCreate(['name' => UserRole::Developer->value, 'guard_name' => 'web']);
    $user->assignRole(UserRole::Developer->value);

    return $user->fresh();
}

test('a user without cai-directory.view is denied access to the map page', function (): void {
    $user = grantCaiDirectoryMapAccess(userWithPermissions());

    expect(CaiSectionsMap::canAccess())->toBeFalse();

    $this->actingAs($user);

    $this->get(CaiSectionsMap::getUrl())->assertForbidden();
});

test('a user with cai-directory.view sees only geolocated sections on the map', function (): void {
    $user = grantCaiDirectoryMapAccess(userWithPermissions(PermissionEnum::CaiDirectoryView));
    $geolocated = CaiSection::create([
        'codice_cai' => 'CAI-GEO',
        'name' => 'Sezione Geolocalizzata',
        'region' => 'LOMBARDIA',
        'latitude' => 45.4642,
        'longitude' => 9.19,
    ]);
    CaiSection::create([
        'codice_cai' => 'CAI-NOCOORD',
        'name' => 'Sezione Senza Coordinate',
        'region' => 'LOMBARDIA',
    ]);

    $this->actingAs($user);

    expect(CaiSectionsMap::canAccess())->toBeTrue();

    Livewire::test(CaiSectionsMap::class)
        ->assertOk()
        ->assertSee('Sezione Geolocalizzata');

    $sections = (new CaiSectionsMap)->sections();

    expect($sections)->toHaveCount(1)
        ->and($sections[0]['codice_cai'])->toBe($geolocated->codice_cai);
});
