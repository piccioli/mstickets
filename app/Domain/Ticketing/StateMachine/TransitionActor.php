<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\StateMachine;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Models\Ticket;

/**
 * "Chi" di una riga della tabella dichiarativa (§6.1.3, colonna "Chi"): non un ruolo,
 * ma un attore verificato sia a livello di permesso (§9.3) sia di rapporto col record
 * per i non-admin/manager (§9.5). Un developer generico (senza rapporto col ticket)
 * non è mai un attore valido: o è già assignee/tester, o sta auto-assegnandosi
 * contestualmente, o la riga non richiede alcun rapporto perché il ticket non ha
 * ancora un assignee (vedi `NoRelationRequired`).
 */
enum TransitionActor
{
    /** `ticket.transition.any` (admin/manager, §9.4): nessun vincolo di rapporto col record. */
    case AdminOrManager;

    /** `ticket.update.assigned` e `assignee_id` del ticket è l'utente corrente. */
    case Assignee;

    /** `ticket.update.assigned` e `tester_id` del ticket è l'utente corrente. */
    case Tester;

    /**
     * `ticket.update.assigned` (developer) su una transizione che valorizza
     * `assignee_id` (guard "assignee_id valorizzato", §6.1.3): ammesso solo se il
     * contesto passato alla transizione assegna il ticket proprio all'utente corrente
     * (auto-assegnazione esplicita, non implicita — AC US-101).
     */
    case AutoAssigningDeveloper;

    /**
     * `ticket.update.assigned` (developer) su una transizione che non richiede alcun
     * rapporto col record perché il ticket non ha ancora un assignee (es. `new → backlog`).
     */
    case NoRelationRequired;

    /** Utente di sistema (§6.2.1): mai raggiungibile da UI, solo da comandi/listener. */
    case System;

    /**
     * @param  array<string, mixed>  $context
     */
    public function authorize(User $user, Ticket $ticket, array $context): bool
    {
        return match ($this) {
            self::AdminOrManager => $user->can(Permission::TicketTransitionAny),
            self::Assignee => $user->can(Permission::TicketUpdateAssigned) && $ticket->assignee_id !== null
                && $ticket->assignee_id === $user->id,
            self::Tester => $user->can(Permission::TicketUpdateAssigned) && $ticket->tester_id !== null
                && $ticket->tester_id === $user->id,
            self::AutoAssigningDeveloper => $user->can(Permission::TicketUpdateAssigned)
                && array_key_exists('assignee_id', $context)
                && (int) $context['assignee_id'] === $user->id,
            self::NoRelationRequired => $user->can(Permission::TicketUpdateAssigned),
            self::System => $user->isSystem(),
        };
    }
}
