<?php

declare(strict_types=1);

namespace App\Domain\Fundraising\Policies;

use App\Domain\Fundraising\Models\FundraisingProject;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;

/**
 * Il catalogo §9.3 non distingue permessi opportunità/progetto: riusa lo stesso set
 * `fundraising.*` di {@see FundraisingOpportunityPolicy}.
 */
class FundraisingProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny([Permission::FundraisingViewAny, Permission::FundraisingViewInvolved]);
    }

    public function view(User $user, FundraisingProject $fundraisingProject): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::FundraisingCreate);
    }

    public function update(User $user, FundraisingProject $fundraisingProject): bool
    {
        return $user->can(Permission::FundraisingUpdate);
    }

    public function delete(User $user, FundraisingProject $fundraisingProject): bool
    {
        return $user->can(Permission::FundraisingDelete);
    }
}
