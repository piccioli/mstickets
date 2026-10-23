<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Models\CaiHut;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function hutCustomer(CustomerType $type, ?Region $region = null): User
{
    $user = withRole(User::factory()->create(), UserRole::Customer);
    $user->forceFill(['customer_type' => $type, 'region' => $region])->save();

    return $user;
}

beforeEach(function (): void {
    $this->owner = hutCustomer(CustomerType::Sezione);
    caiSection(['codice_cai' => '9226005', 'region' => 'TOSCANA', 'user_id' => $this->owner->id]);
    caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);
    $this->hut = CaiHut::create(['id_cai' => 1, 'name' => 'Rifugio Rossi', 'branch_code' => '9226005']);
});

test('staff with cai directory view, owner section and its regional group see the hut and its management', function (): void {
    $staff = userWithPermissions(Permission::CaiDirectoryView);
    $group = hutCustomer(CustomerType::GruppoRegionale, Region::Toscana);

    foreach ([$staff, $this->owner, $group] as $user) {
        expect($user->can('view', $this->hut))->toBeTrue()
            ->and($user->can('viewManagement', $this->hut))->toBeTrue();
    }
});

test('another section, another regional group and a user without permissions are denied', function (): void {
    $other = hutCustomer(CustomerType::Sezione);
    caiSection(['codice_cai' => '9226002', 'region' => 'TOSCANA', 'user_id' => $other->id]);
    caiSection(['codice_cai' => '9203001', 'region' => 'LOMBARDIA']);
    $otherGroup = hutCustomer(CustomerType::GruppoRegionale, Region::Lombardia);

    foreach ([$other, $otherGroup, User::factory()->create()] as $user) {
        expect($user->can('view', $this->hut))->toBeFalse()
            ->and($user->can('viewManagement', $this->hut))->toBeFalse();
    }
});

test('huts are read-only for everyone', function (): void {
    $staff = userWithPermissions(Permission::CaiDirectoryView);

    expect($staff->can('create', CaiHut::class))->toBeFalse()
        ->and($staff->can('update', $this->hut))->toBeFalse()
        ->and($staff->can('delete', $this->hut))->toBeFalse();
});

test('a regional group sees the huts registered directly to it, not those of another group', function (): void {
    caiSection(['codice_cai' => '9228001', 'region' => 'SICILIA']);
    $hut = CaiHut::create(['id_cai' => 2, 'name' => 'Punto Etna', 'branch_code' => '9500046', 'branch_name' => 'GR SICILIA']);

    expect(hutCustomer(CustomerType::GruppoRegionale, Region::Sicilia)->can('view', $hut))->toBeTrue()
        ->and(hutCustomer(CustomerType::GruppoRegionale, Region::Toscana)->can('view', $hut))->toBeFalse();
});
