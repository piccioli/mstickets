<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\StateMachine;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use Closure;

/**
 * Una riga della tabella dichiarativa di §6.1.3: `from`, `to`, `actors` ("chi", vedi
 * `TransitionActor`), `guard` (precondizione, con messaggio localizzato) ed `effects`
 * (side effect dichiarati, eseguiti da US-103).
 *
 * `to` è `null` per le due righe "torna allo stato precedente" (`waiting`/`problem` →
 * `previous_status`, §6.1.3): il target non è uno stato fisso della tabella ma dipende
 * dalla colonna `previous_status` del singolo ticket. `matchesTarget()` risolve il
 * confronto dinamicamente.
 */
final class Transition
{
    /**
     * @param  list<TicketStatus>  $from
     * @param  list<TransitionActor>  $actors
     * @param  list<TransitionEffect>  $effects
     */
    public function __construct(
        public readonly array $from,
        public readonly ?TicketStatus $to,
        public readonly array $actors,
        public readonly ?Closure $guard = null,
        public readonly ?string $guardMessage = null,
        public readonly array $effects = [],
    ) {}

    public function appliesTo(TicketStatus $from): bool
    {
        return in_array($from, $this->from, strict: true);
    }

    public function matchesTarget(Ticket $ticket, TicketStatus $to): bool
    {
        if ($this->to !== null) {
            return $this->to === $to;
        }

        return $ticket->previous_status !== null && $ticket->previous_status === $to;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function isAuthorizedFor(User $user, Ticket $ticket, array $context): bool
    {
        foreach ($this->actors as $actor) {
            if ($actor->authorize($user, $ticket, $context)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function guardPasses(Ticket $ticket, array $context): bool
    {
        if ($this->guard === null) {
            return true;
        }

        return ($this->guard)($ticket, $context);
    }
}
