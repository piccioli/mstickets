<?php

declare(strict_types=1);

namespace App\Domain\Identity\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use STS\FilamentImpersonate\Facades\Impersonation;

class UserPolicy
{
    /**
     * Guardia raccomandata dal README di `stechstudio/filament-impersonate` (US-607, §6.7.2):
     * quando un admin impersona un utente senza `user.view.any` (es. un customer) dalla
     * pagina lista utenti, i componenti Livewire della tabella tentano un re-render prima
     * che il redirect post-impersonation avvenga, causando un 403 spurio. Nessun impatto
     * sull'autorizzazione reale: un utente impersonato resta comunque scoped/soggetto a
     * tutte le altre Policy per la durata dell'impersonation.
     */
    public function viewAny(User $user): bool
    {
        if (Impersonation::isImpersonating()) {
            return true;
        }

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
