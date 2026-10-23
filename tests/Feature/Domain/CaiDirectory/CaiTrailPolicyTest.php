<?php

declare(strict_types=1);

use App\Domain\CaiDirectory\Models\CaiTrail;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function trailCustomer(CustomerType $type, ?Region $region = null): User
{
    $user = withRole(User::factory()->create(), UserRole::Customer);
    $user->forceFill(['customer_type' => $type, 'region' => $region])->save();

    return $user;
}

beforeEach(function (): void {
    $this->owner = trailCustomer(CustomerType::Sezione);
    $this->partner = trailCustomer(CustomerType::Sezione);
    caiSection(['codice_cai' => '9226005', 'region' => 'TOSCANA', 'user_id' => $this->owner->id]);
    caiSection(['codice_cai' => '9226001', 'region' => 'TOSCANA']);
    caiSection(['codice_cai' => '9208001', 'region' => 'EMILIA-ROMAGNA', 'user_id' => $this->partner->id]);
    $this->trail = CaiTrail::create(['id' => 1, 'status' => 4, 'practicability' => 'percorribile']);
    DB::table('cai_trail_section')->insert([
        ['cai_trail_id' => 1, 'section_code' => '9226005'],
        ['cai_trail_id' => 1, 'section_code' => '9208001'],
    ]);
});

test('staff, every section of a shared trail and the regional group of one of them see the trail', function (): void {
    $staff = userWithPermissions(Permission::CaiDirectoryView);
    $group = trailCustomer(CustomerType::GruppoRegionale, Region::Toscana);

    foreach ([$staff, $this->owner, $this->partner, $group] as $user) {
        expect($user->can('view', $this->trail))->toBeTrue();
    }
});

test('another section, another regional group and a user without permissions are denied', function (): void {
    $other = trailCustomer(CustomerType::Sezione);
    caiSection(['codice_cai' => '9226002', 'region' => 'TOSCANA', 'user_id' => $other->id]);
    caiSection(['codice_cai' => '9203001', 'region' => 'LOMBARDIA']);
    $otherGroup = trailCustomer(CustomerType::GruppoRegionale, Region::Lombardia);

    foreach ([$other, $otherGroup, User::factory()->create()] as $user) {
        expect($user->can('view', $this->trail))->toBeFalse();
    }
});

test('a trail without sections is visible only to the staff', function (): void {
    $orphan = CaiTrail::create(['id' => 2, 'status' => 1, 'practicability' => 'sconosciuto']);

    expect($this->owner->can('view', $orphan))->toBeFalse()
        ->and(userWithPermissions(Permission::CaiDirectoryView)->can('view', $orphan))->toBeTrue();
});

test('a regional group sees the trails present in its region (at least 50%), with or without sections', function (): void {
    DB::table('cai_trail_regions')->insert([
        ['id' => 1, 'name' => 'Lombardia', 'region_key' => 'lombardia'],
        ['id' => 2, 'name' => 'Toscana', 'region_key' => 'toscana'],
    ]);
    $orphan = CaiTrail::create(['id' => 2, 'status' => 1, 'practicability' => 'sconosciuto']);
    $border = CaiTrail::create(['id' => 3, 'status' => 1, 'practicability' => 'sconosciuto']);
    DB::table('cai_trail_territory')->insert([
        ['cai_trail_id' => 2, 'region_id' => 1, 'province_id' => null, 'sector_id' => null, 'percentage' => 50],
        ['cai_trail_id' => 3, 'region_id' => 1, 'province_id' => null, 'sector_id' => null, 'percentage' => 49.99],
        // Il sentiero di Toscana ed Emilia è presente anche in Lombardia: lo vede pure quel GR.
        ['cai_trail_id' => 1, 'region_id' => 1, 'province_id' => null, 'sector_id' => null, 'percentage' => 60],
    ]);
    $lombardia = trailCustomer(CustomerType::GruppoRegionale, Region::Lombardia);
    $toscana = trailCustomer(CustomerType::GruppoRegionale, Region::Toscana);

    expect($lombardia->can('view', $orphan))->toBeTrue()
        ->and($lombardia->can('view', $border))->toBeFalse()
        ->and($lombardia->can('view', $this->trail))->toBeTrue()
        ->and($toscana->can('view', $orphan))->toBeFalse()
        ->and($this->owner->can('view', $orphan))->toBeFalse();
});

test('trails are read-only for everyone', function (): void {
    $staff = userWithPermissions(Permission::CaiDirectoryView);

    expect($staff->can('create', CaiTrail::class))->toBeFalse()
        ->and($staff->can('update', $this->trail))->toBeFalse()
        ->and($staff->can('delete', $this->trail))->toBeFalse();
});
