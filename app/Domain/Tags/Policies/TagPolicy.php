<?php

declare(strict_types=1);

namespace App\Domain\Tags\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Tags\Models\Tag;

class TagPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::TagView);
    }

    public function view(User $user, Tag $tag): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::TagCreate);
    }

    public function update(User $user, Tag $tag): bool
    {
        return $user->can(Permission::TagUpdate);
    }

    public function delete(User $user, Tag $tag): bool
    {
        return $user->can(Permission::TagDelete);
    }

    public function restore(User $user, Tag $tag): bool
    {
        return $user->can(Permission::TagDelete);
    }

    public function forceDelete(User $user, Tag $tag): bool
    {
        return $user->can(Permission::TagDelete);
    }
}
