<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

/**
 * §8.4, US-602: un customer vede in navigazione SOLO il gruppo "Area
 * cliente" — nessuna voce dei gruppi staff (Ticket, Rendicontazione,
 * Documentazione, Lavoro, Fundraising staff, ecc.) deve comparire, a
 * differenza di uno staff member che vede i gruppi staff invariati.
 */
test('a customer sees only the Area cliente navigation group', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $customer = withRole(User::factory()->create(), UserRole::Customer);

    $this->actingAs($customer);

    $groupLabels = collect(Filament::getNavigation())
        ->map(fn ($group) => $group->getLabel())
        ->filter()
        ->unique()
        ->values()
        ->all();

    expect($groupLabels)->toBe(['Area cliente']);
});

test('a staff member does not see the Area cliente navigation group', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $manager = withRole(User::factory()->create(), UserRole::Manager);

    $this->actingAs($manager);

    $groupLabels = collect(Filament::getNavigation())
        ->map(fn ($group) => $group->getLabel())
        ->filter()
        ->all();

    expect($groupLabels)->not->toContain('Area cliente');
});
