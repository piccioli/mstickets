<?php

declare(strict_types=1);

use App\Domain\Identity\Models\User;
use App\Import\Enums\ImportRunStatus;
use App\Import\Models\ImportRun;
use App\Import\Stages\ImportContext;
use App\Import\Stages\RolesPermissionsStage;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Import\Fixtures\InteractsWithLegacyDatabase;

uses(RefreshDatabase::class, InteractsWithLegacyDatabase::class);

function rolesPermissionsStageContext(bool $dryRun = false): ImportContext
{
    $importRun = ImportRun::create([
        'started_at' => now(),
        'dump_label' => 'test-dump',
        'stages' => [],
        'status' => ImportRunStatus::Running,
        'is_dry_run' => $dryRun,
    ]);

    return new ImportContext(importRun: $importRun, dryRun: $dryRun);
}

beforeEach(function (): void {
    $this->useSqliteLegacyConnection();

    Schema::connection('legacy')->create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('password');
        $table->string('roles')->nullable();
        $table->timestamps();
    });
});

function insertLegacyUserWithRoles(int $id, ?string $roles, ?string $email = null): User
{
    $email ??= "user{$id}@example.test";

    DB::connection('legacy')->table('users')->insert([
        'id' => $id,
        'name' => "User {$id}",
        'email' => $email,
        'password' => 'bcrypt-hash',
        'roles' => $roles,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return User::query()->create([
        'id' => $id,
        'name' => "User {$id}",
        'email' => $email,
        'password' => 'bcrypt-hash',
    ]);
}

test('fails explicitly if RolePermissionSeeder has not run yet', function (): void {
    insertLegacyUserWithRoles(1, '["developer"]');

    expect(fn () => (new RolesPermissionsStage)->run(rolesPermissionsStageContext()))
        ->toThrow(RuntimeException::class, 'RolePermissionSeeder');
});

test('assigns a recognized role via Spatie', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $user = insertLegacyUserWithRoles(1, '["developer","fundraising"]');

    $result = (new RolesPermissionsStage)->run(rolesPermissionsStageContext());

    expect($user->fresh()->hasRole('developer'))->toBeTrue()
        ->and($user->fresh()->hasRole('fundraising'))->toBeTrue()
        ->and($result->created)->toBe(1)
        ->and($result->warnings)->toBe([
            'Developer esistenti (candidati manuali per horizon.access/logs.access, non assegnati automaticamente): id v1 [1].',
        ]);
});

test('editor grants direct documentation permissions instead of a role, and is flagged if it was the only role', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $user = insertLegacyUserWithRoles(1, '["editor"]');

    $result = (new RolesPermissionsStage)->run(rolesPermissionsStageContext());

    $fresh = $user->fresh();

    expect($fresh->roles)->toHaveCount(0)
        ->and($fresh->hasDirectPermission('documentation.create'))->toBeTrue()
        ->and($fresh->hasDirectPermission('documentation.update'))->toBeTrue()
        ->and($result->warnings)->toContain(
            'Utente v1 #1 (user1@example.test): "editor" era l\'unico ruolo v1 — nessun ruolo v2 assegnato (permessi diretti concessi), decidere manualmente il ruolo.'
        );
});

test('unrecognized role tokens are discarded and reported', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $user = insertLegacyUserWithRoles(1, '["developer","ghost"]');

    $result = (new RolesPermissionsStage)->run(rolesPermissionsStageContext());

    expect($user->fresh()->hasRole('developer'))->toBeTrue()
        ->and($result->warnings)->toContain('Utente v1 #1 (user1@example.test): ruolo non riconosciuto "ghost" scartato.');
});

test('a user with no roles at all is reported', function (): void {
    $this->seed(RolePermissionSeeder::class);
    insertLegacyUserWithRoles(1, '[]');

    $result = (new RolesPermissionsStage)->run(rolesPermissionsStageContext());

    expect($result->warnings)->toContain('Utente v1 #1 (user1@example.test): nessun ruolo v2 valido — non potrà accedere al pannello.');
});

test('a non-parsable roles value assigns no role and is reported', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $user = insertLegacyUserWithRoles(1, 'not-json');

    $result = (new RolesPermissionsStage)->run(rolesPermissionsStageContext());

    expect($user->fresh()->roles)->toHaveCount(0)
        ->and($result->warnings)->toContain('Utente v1 #1 (user1@example.test): valore users.roles non parsabile ("not-json") — nessun ruolo assegnato.');
});

test('re-running the stage on the same dump is idempotent: no duplicate role/permission rows', function (): void {
    $this->seed(RolePermissionSeeder::class);
    insertLegacyUserWithRoles(1, '["developer"]');
    insertLegacyUserWithRoles(2, '["editor"]');

    $stage = new RolesPermissionsStage;
    $first = $stage->run(rolesPermissionsStageContext());
    $second = $stage->run(rolesPermissionsStageContext());

    expect($first->created)->toBe(2)
        ->and($second->created)->toBe(0)
        ->and($second->skipped)->toBe(2)
        ->and(DB::table('model_has_roles')->count())->toBe(1)
        ->and(DB::table('model_has_permissions')->count())->toBe(2);
});

test('--dry-run does not assign any role or permission', function (): void {
    $this->seed(RolePermissionSeeder::class);
    $user = insertLegacyUserWithRoles(1, '["developer"]');

    (new RolesPermissionsStage)->run(rolesPermissionsStageContext(dryRun: true));

    expect($user->fresh()->roles)->toHaveCount(0);
});
