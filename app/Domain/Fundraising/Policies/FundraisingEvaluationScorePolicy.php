<?php

declare(strict_types=1);

namespace App\Domain\Fundraising\Policies;

use App\Domain\Fundraising\Models\FundraisingEvaluationScore;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;

class FundraisingEvaluationScorePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny([Permission::FundraisingViewAny, Permission::FundraisingViewInvolved]);
    }

    public function view(User $user, FundraisingEvaluationScore $fundraisingEvaluationScore): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Scrivere un punteggio di valutazione è l'azione `fundraising.evaluate` (§9.3), non una
     * `create`/`update`/`delete` generica.
     */
    public function create(User $user): bool
    {
        return $user->can(Permission::FundraisingEvaluate);
    }

    public function update(User $user, FundraisingEvaluationScore $fundraisingEvaluationScore): bool
    {
        return $user->can(Permission::FundraisingEvaluate);
    }

    public function delete(User $user, FundraisingEvaluationScore $fundraisingEvaluationScore): bool
    {
        return $user->can(Permission::FundraisingEvaluate);
    }
}
