<?php

declare(strict_types=1);

namespace App\Domain\Identity\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::UserView);
    }

    public function view(User $user, User $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::UserCreate);
    }

    public function update(User $user, User $model): bool
    {
        return $user->can(Permission::UserUpdate);
    }

    /**
     * "Eliminare" un utente in questo dominio è la soft-delete già mappata sul permesso di
     * disattivazione (§9.3 non ha un `user.delete` distinto): l'eliminazione irreversibile
     * (`forceDelete`) resta negata a chiunque.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->can(Permission::UserDeactivate);
    }

    public function restore(User $user, User $model): bool
    {
        return $user->can(Permission::UserUpdate);
    }

    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }

    public function deactivate(User $user, User $model): bool
    {
        return $user->can(Permission::UserDeactivate);
    }

    public function assignRoles(User $user, User $model): bool
    {
        return $user->can(Permission::UserAssignRoles);
    }

    public function grantPermissions(User $user, User $model): bool
    {
        return $user->can(Permission::UserGrantPermissions);
    }

    public function impersonate(User $user, User $model): bool
    {
        return $user->can(Permission::UserImpersonate);
    }
}
