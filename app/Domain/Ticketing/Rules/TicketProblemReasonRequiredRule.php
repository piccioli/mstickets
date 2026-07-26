<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Rules;

use App\Domain\Ticketing\StateMachine\TicketStateMachine;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Regola di dominio (PRD A3): la transizione verso `problem` richiede `problem_reason`
 * non vuoto (§6.1.3). Usata sia dal guard di {@see TicketStateMachine}
 * sia da qualunque punto di ingresso futuro (form Filament, API) sullo stesso campo,
 * senza duplicare la condizione o il messaggio.
 */
final class TicketProblemReasonRequiredRule implements ValidationRule
{
    public const string MESSAGE = 'Il motivo del blocco è obbligatorio.';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            $fail(self::MESSAGE);
        }
    }
}
