<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Queries;

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Interni" (§8.5): ticket con richiedente valorizzato il cui richiedente NON ha il ruolo
 * customer (staff che apre un ticket per sé), non ancora completati. Usa
 * `whereDoesntHave('roles', ...)` invece dello scope `withoutRole()` di Spatie: quello
 * scope risolve il ruolo con `findByName()` e lancia `RoleDoesNotExist` se la riga
 * `roles` non esiste ancora, mentre qui l'assenza del ruolo deve solo significare
 * "nessun richiedente ha quel ruolo", mai un'eccezione (vedi anche
 * {@see AllCustomerTicketsQuery}).
 */
final class InternalTicketsQuery
{
    /**
     * @return Builder<Ticket>
     */
    public static function for(User $user): Builder
    {
        return Ticket::query()->visibleTo($user)
            ->whereNotNull('requester_id')
            ->whereHas(
                'requester',
                fn (Builder $query): Builder => $query->whereDoesntHave(
                    'roles',
                    fn (Builder $query): Builder => $query->where('name', UserRole::Customer->value),
                ),
            )
            ->where('status', '!=', TicketStatus::Done);
    }
}
