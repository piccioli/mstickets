<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

/**
 * @return array<string, list<string>> etichetta voce di primo livello → etichette dei figli, nel gruppo "Anagrafica CAI"
 */
function caiNavigationTree(): array
{
    /** @var NavigationGroup|null $group */
    $group = collect(Filament::getNavigation())->first(fn (NavigationGroup $g): bool => $g->getLabel() === 'Anagrafica CAI');

    if ($group === null) {
        return [];
    }

    return collect($group->getItems())
        ->mapWithKeys(fn ($item): array => [
            $item->getLabel() => collect($item->getChildItems())->map(fn ($child) => $child->getLabel())->values()->all(),
        ])
        ->all();
}

/**
 * @return list<string> etichette delle voci figlie del sotto-menu "Bilanci", annidato dentro "Sezioni"
 */
function caiBilanciChildren(): array
{
    /** @var NavigationGroup|null $group */
    $group = collect(Filament::getNavigation())->first(fn (NavigationGroup $g): bool => $g->getLabel() === 'Anagrafica CAI');
    $sezioni = collect($group?->getItems())->first(fn ($item): bool => $item->getLabel() === 'Sezioni');
    $bilanci = collect($sezioni?->getChildItems())->first(fn ($item): bool => $item->getLabel() === 'Bilanci');

    // I figli annidati non passano dal filtro di visibilità di Filament: li salta la vista sidebar (isVisible()).
    return collect($bilanci?->getChildItems())
        ->filter(fn ($child): bool => $child->isVisible())
        ->map(fn ($child) => $child->getLabel())
        ->values()
        ->all();
}

function caiStaffUser(PermissionEnum ...$permissions): User
{
    $user = userWithPermissions(...$permissions);
    Role::query()->firstOrCreate(['name' => UserRole::Developer->value, 'guard_name' => 'web']);
    $user->assignRole(UserRole::Developer->value);

    return $user->fresh();
}

test('an admin sees the Anagrafica CAI group organised in ordered sub-menus', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(withRole(User::factory()->create(), UserRole::Admin));

    expect(caiNavigationTree())->toBe([
        'Sezioni' => ['Anagrafica sezioni', 'Mappa sezioni', 'Bilanci'],
        'Gruppi regionali' => ['Elenco gruppi regionali'],
    ]);
    expect(caiBilanciChildren())->toBe(['Bilanci non interpretati', 'Bilancio 2025']);
});

test('a user with only cai-directory.view sees Sezioni and only Bilancio 2025 under the nested Bilanci', function (): void {
    $this->actingAs(caiStaffUser(PermissionEnum::CaiDirectoryView));

    expect(caiNavigationTree())->toBe([
        'Sezioni' => ['Anagrafica sezioni', 'Mappa sezioni', 'Bilanci'],
        'Gruppi regionali' => ['Elenco gruppi regionali'],
    ]);
    expect(caiBilanciChildren())->toBe(['Bilancio 2025']);
});

test('a user without cai-directory permissions does not see the Anagrafica CAI group', function (): void {
    $this->actingAs(caiStaffUser());

    expect(caiNavigationTree())->toBe([]);
});
