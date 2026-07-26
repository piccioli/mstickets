<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\StateMachine;

/**
 * Effetto collaterale dichiarato da una riga della tabella (§6.1.3, colonna "Effetti").
 * Solo metadato in questa fase: l'esecuzione (scrittura di `tickets`/`ticket_logs`,
 * emissione degli eventi, notifiche) è responsabilità dell'Action `ChangeTicketStatus`
 * (US-103), non della macchina a stati. La macchina a stati resta pura: dichiara che
 * un effetto è previsto, non lo esegue.
 */
enum TransitionEffect
{
    /** Demote §6.1.4: gli altri ticket `progress` dello stesso assignee passano a `todo`. */
    case DemoteOtherProgressTickets;

    /** `released_at = now()`. */
    case SetReleasedAt;

    /** `done_at = now()` (§6.1.5). */
    case SetDoneAt;

    /** Salva `previous_status` prima di entrare in `waiting`/`problem`. */
    case SavePreviousStatus;

    /** Ripristina lo stato da `previous_status`, mantenendo `waiting_reason`/`problem_reason`. */
    case RestorePreviousStatus;
}
