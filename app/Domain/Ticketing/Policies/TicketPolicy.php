<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Models\Ticket;

/**
 * Livello 1 (permesso) + livello 2 (rapporto col record, §9.5): `view()`/`update()` non si
 * fermano al permesso, verificano anche il rapporto col ticket (requester/assignee/tester)
 * per i ruoli non-admin/manager.
 */
class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny([Permission::TicketViewAny, Permission::TicketViewOwn, Permission::TicketViewAssigned]);
    }

    /**
     * Delega a {@see Ticket::scopeVisibleTo()}, unica fonte di verità sul rapporto
     * permesso+record (§9.5): un cliente è negato se `requester_id` non è il suo, a
     * meno che non abbia `ticket.view.any`.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        return Ticket::query()->whereKey($ticket->getKey())->visibleTo($user)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::TicketCreate);
    }

    /**
     * Un utente con solo `ticket.update.assigned` (developer) è negato se non è
     * `assignee_id` né `tester_id` del ticket (§9.5); con `ticket.update.own`
     * (cliente) è negato se non è `requester_id`; `ticket.update.any` (admin/manager)
     * non ha vincoli di record.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        if ($user->can(Permission::TicketUpdateAny)) {
            return true;
        }

        if ($user->can(Permission::TicketUpdateOwn) && $ticket->requester_id === $user->id) {
            return true;
        }

        return $user->can(Permission::TicketUpdateAssigned)
            && ($ticket->assignee_id === $user->id || $ticket->tester_id === $user->id);
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->can(Permission::TicketDelete);
    }

    public function restore(User $user, Ticket $ticket): bool
    {
        return $user->can(Permission::TicketDelete);
    }

    public function forceDelete(User $user, Ticket $ticket): bool
    {
        return $user->can(Permission::TicketDelete);
    }
}
