<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Tags\Models\Tag;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a user without tag.* permissions is denied every TagPolicy ability', function (): void {
    $actor = userWithPermissions();
    $tag = Tag::create(['name' => 'Commessa A', 'slug' => 'commessa-a']);

    expect($actor->can('viewAny', Tag::class))->toBeFalse()
        ->and($actor->can('view', $tag))->toBeFalse()
        ->and($actor->can('create', Tag::class))->toBeFalse()
        ->and($actor->can('update', $tag))->toBeFalse()
        ->and($actor->can('delete', $tag))->toBeFalse()
        ->and($actor->can('restore', $tag))->toBeFalse()
        ->and($actor->can('forceDelete', $tag))->toBeFalse();
});

test('a user with the matching tag.* permission is authorized', function (): void {
    $tag = Tag::create(['name' => 'Commessa A', 'slug' => 'commessa-a']);

    expect(userWithPermissions(PermissionEnum::TagView)->can('view', $tag))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::TagCreate)->can('create', Tag::class))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::TagUpdate)->can('update', $tag))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::TagDelete)->can('delete', $tag))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::TagDelete)->can('restore', $tag))->toBeTrue()
        ->and(userWithPermissions(PermissionEnum::TagDelete)->can('forceDelete', $tag))->toBeTrue();
});

test('TagPolicy per ruolo, riga per riga (§9.4)', function (UserRole $role, bool $view, bool $create, bool $update, bool $delete): void {
    $this->seed(RolePermissionSeeder::class);

    $tag = Tag::create(['name' => 'Commessa A', 'slug' => 'commessa-a-'.$role->value]);
    $user = withRole(User::factory()->create(), $role);

    expect($user->can('viewAny', Tag::class))->toBe($view)
        ->and($user->can('view', $tag))->toBe($view)
        ->and($user->can('create', Tag::class))->toBe($create)
        ->and($user->can('update', $tag))->toBe($update)
        ->and($user->can('delete', $tag))->toBe($delete)
        ->and($user->can('restore', $tag))->toBe($delete)
        ->and($user->can('forceDelete', $tag))->toBe($delete);
})->with([
    'admin — CRUD completo' => [UserRole::Admin, true, true, true, true],
    'manager — view/create/update, mai delete' => [UserRole::Manager, true, true, true, false],
    'developer — solo view' => [UserRole::Developer, true, false, false, false],
    'customer — nessun accesso' => [UserRole::Customer, false, false, false, false],
    'fundraising — nessun accesso' => [UserRole::Fundraising, false, false, false, false],
]);
