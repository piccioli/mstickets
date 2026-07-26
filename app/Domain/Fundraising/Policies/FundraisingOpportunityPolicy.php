<?php

declare(strict_types=1);

namespace App\Domain\Fundraising\Policies;

use App\Domain\Fundraising\Models\FundraisingOpportunity;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;

class FundraisingOpportunityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny([Permission::FundraisingViewAny, Permission::FundraisingViewInvolved]);
    }

    public function view(User $user, FundraisingOpportunity $fundraisingOpportunity): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::FundraisingCreate);
    }

    public function update(User $user, FundraisingOpportunity $fundraisingOpportunity): bool
    {
        return $user->can(Permission::FundraisingUpdate);
    }

    public function delete(User $user, FundraisingOpportunity $fundraisingOpportunity): bool
    {
        return $user->can(Permission::FundraisingDelete);
    }

    public function evaluate(User $user, FundraisingOpportunity $fundraisingOpportunity): bool
    {
        return $user->can(Permission::FundraisingEvaluate);
    }
}
