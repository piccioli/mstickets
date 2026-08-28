<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Pages\CustomerDashboard;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Import\Enums\ImportRunStatus;
use App\Import\Models\ImportRun;
use App\Import\Stages\CustomerClassificationStage;
use App\Import\Stages\ImportContext;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Checkpoint di fine Fase 7 (§14 del PRD, US-706): percorre end-to-end il flusso
 * completo di questa fase — classificazione automatica al momento dell'"import"
 * (qui lo stage `CustomerClassificationStage` gira direttamente sui 4 pattern,
 * stesso principio già adottato dal checkpoint di Fase 6 per cui un test Pest
 * gira sempre contro un database di test, mai contro il dump v1 reale — quella
 * verifica su dati reali è stata condotta manualmente durante US-702/US-703/
 * US-704/US-705 e documentata in progress.txt) → correzione manuale da parte di
 * un admin di un utente già classificato → la dashboard del cliente corretto
 * riflette il nuovo tipo, non quello dedotto automaticamente.
 */
uses(RefreshDatabase::class);

function runCustomerClassificationStage(): void
{
    $importRun = ImportRun::create([
        'started_at' => now(),
        'dump_label' => 'fase-7-checkpoint',
        'stages' => [],
        'status' => ImportRunStatus::Running,
        'is_dry_run' => false,
    ]);

    (new CustomerClassificationStage)->run(new ImportContext(importRun: $importRun, dryRun: false));
}

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
});

test('import classifies one user of each customer type correctly, an admin corrects one manually, and the customer dashboard reflects the corrected type', function (): void {
    $sezione = User::factory()->create(['name' => 'Sezione di Bergamo | Lombardia']);
    $sezione->assignRole(UserRole::Customer->value);

    $gruppoRegionale = User::factory()->create(['name' => 'GR Abruzzo']);
    $gruppoRegionale->assignRole(UserRole::Customer->value);

    $otcoSo = User::factory()->create(['name' => 'OTCO/SO Commissione Centrale']);
    $otcoSo->assignRole(UserRole::Customer->value);

    $generico = User::factory()->create(['name' => 'Mario Rossi']);
    $generico->assignRole(UserRole::Customer->value);

    runCustomerClassificationStage();

    expect($sezione->fresh()->customer_type)->toBe(CustomerType::Sezione)
        ->and($sezione->fresh()->region)->toBe(Region::Lombardia)
        ->and($gruppoRegionale->fresh()->customer_type)->toBe(CustomerType::GruppoRegionale)
        ->and($gruppoRegionale->fresh()->region)->toBe(Region::Abruzzo)
        ->and($otcoSo->fresh()->customer_type)->toBe(CustomerType::OrganoTecnicoStrutturaOperativa)
        ->and($otcoSo->fresh()->region)->toBeNull()
        ->and($generico->fresh()->customer_type)->toBe(CustomerType::Generico)
        ->and($generico->fresh()->region)->toBeNull();

    // Un admin corregge manualmente la Sezione dedotta dall'import: diventa un
    // Gruppo Regionale del Veneto, non più una Sezione della Lombardia.
    $admin = User::factory()->create();
    SpatieRole::query()->firstOrCreate(['name' => UserRole::Developer->value, 'guard_name' => 'web']);
    $admin->assignRole(UserRole::Developer->value);
    $admin->givePermissionTo([
        PermissionEnum::UserView->value,
        PermissionEnum::UserUpdate->value,
        PermissionEnum::UserAssignRoles->value,
    ]);

    $customerRoleId = SpatieRole::query()->where('name', UserRole::Customer->value)->value('id');

    $this->actingAs($admin);

    Livewire::test(EditUser::class, ['record' => $sezione->getKey()])
        ->fillForm([
            'roles' => [$customerRoleId],
            'customer_type' => CustomerType::GruppoRegionale->value,
            'region' => Region::Veneto->value,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $sezione->refresh();
    expect($sezione->customer_type)->toBe(CustomerType::GruppoRegionale)
        ->and($sezione->region)->toBe(Region::Veneto);

    // La dashboard del cliente corretto mostra il nuovo tipo (Gruppo Regionale —
    // Veneto), mai quello dedotto originariamente dall'import (Sezione — Lombardia).
    $this->actingAs($sezione)
        ->get(CustomerDashboard::getUrl())
        ->assertSuccessful()
        ->assertSee('Gruppo Regionale — Veneto')
        ->assertDontSee('Sezione — Lombardia', false);
});
