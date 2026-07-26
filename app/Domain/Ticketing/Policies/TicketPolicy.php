<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Models\Ticket;

/**
 * Livello 1 (permesso) soltanto (§9.1): le regole legate al singolo record (assignee/tester
 * del ticket) richiedono la macchina a stati e arrivano in Fase 1.
 */
class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny([Permission::TicketViewAny, Permission::TicketViewOwn, Permission::TicketViewAssigned]);
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::TicketCreate);
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $user->canAny([Permission::TicketUpdateAny, Permission::TicketUpdateOwn, Permission::TicketUpdateAssigned]);
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
