<?php

declare(strict_types=1);

use App\Domain\Identity\Enums\Permission as PermissionEnum;
use App\Domain\Tags\Models\Tag;
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
