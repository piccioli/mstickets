<?php

declare(strict_types=1);

namespace App\Domain\Mail\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Mail\Models\NotificationPreference;

/**
 * Il catalogo §9.3 non ha ancora un permesso per "gestire le proprie preferenze": in questa
 * fase (livello 1, solo permesso) si riusa `email.*`. L'autorizzazione "posso gestire le MIE
 * preferenze indipendentemente da `email.manage`" è una regola legata al singolo record
 * (record-ownership) e arriva in Fase 1 insieme alle altre regole di questo tipo (§9.1).
 */
class NotificationPreferencePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::EmailView);
    }

    public function view(User $user, NotificationPreference $notificationPreference): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::EmailManage);
    }

    public function update(User $user, NotificationPreference $notificationPreference): bool
    {
        return $user->can(Permission::EmailManage);
    }

    public function delete(User $user, NotificationPreference $notificationPreference): bool
    {
        return $user->can(Permission::EmailManage);
    }
}
