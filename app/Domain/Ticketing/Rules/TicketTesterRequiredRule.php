<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Rules;

use App\Domain\Ticketing\StateMachine\TicketStateMachine;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Regola di dominio (PRD A3): la transizione verso `testing` richiede `tester_id`
 * valorizzato (§6.1.3). Usata sia dal guard di {@see TicketStateMachine}
 * sia da qualunque punto di ingresso futuro (form Filament, API) sullo stesso campo,
 * senza duplicare la condizione o il messaggio.
 */
final class TicketTesterRequiredRule implements ValidationRule
{
    public const string MESSAGE = 'La transizione richiede di specificare un tester.';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            $fail(self::MESSAGE);
        }
    }
}
