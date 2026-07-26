<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Policies;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Models\TicketLog;

class TicketLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::TicketLogView);
    }

    public function view(User $user, TicketLog $ticketLog): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Nessun permesso di scrittura nel catalogo §9.3: le righe sono scritte solo dal sistema
     * (transizioni di stato, assegnazioni, ecc.), mai da input utente diretto.
     */
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, TicketLog $ticketLog): bool
    {
        return false;
    }

    public function delete(User $user, TicketLog $ticketLog): bool
    {
        return false;
    }
}
