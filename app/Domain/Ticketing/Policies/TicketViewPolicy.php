<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Models\TicketView;

/**
 * Nessun permesso dedicato nel catalogo §9.3: `ticket_views` è solo il marcatore "ultima
 * visita" di un ticket, scritto automaticamente da chi il ticket lo può già vedere.
 */
class TicketViewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny([Permission::TicketViewAny, Permission::TicketViewOwn, Permission::TicketViewAssigned]);
    }

    public function view(User $user, TicketView $ticketView): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, TicketView $ticketView): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, TicketView $ticketView): bool
    {
        return $this->viewAny($user);
    }
}
