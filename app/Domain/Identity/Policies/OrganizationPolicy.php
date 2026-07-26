<?php

declare(strict_types=1);

namespace App\Domain\Identity\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\Organization;
use App\Domain\Identity\Models\User;

class OrganizationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::OrganizationView);
    }

    public function view(User $user, Organization $organization): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::OrganizationCreate);
    }

    public function update(User $user, Organization $organization): bool
    {
        return $user->can(Permission::OrganizationUpdate);
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $user->can(Permission::OrganizationDelete);
    }
}
