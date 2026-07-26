<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Rules;

use App\Domain\Ticketing\Models\Ticket;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Regola di dominio (PRD A3, §6.1.6): un ticket con `parent_id` valorizzato non può a
 * sua volta avere figli — profondità massima 1. Valorizza il campo `parent_id` del
 * ticket in fase di salvataggio: il padre scelto non deve già avere un padre (evita
 * nipoti), e il ticket stesso non deve già avere dei figli (evita che diventi un figlio
 * pur avendo una propria discendenza). Usata sia dalla macchina a stati/Action future
 * (US-103/US-104) sia da qualunque punto di ingresso (form Filament, API), senza
 * duplicare la condizione o il messaggio.
 */
final class TicketParentDepthRule implements ValidationRule
{
    public const string MESSAGE = 'Non è ammessa una gerarchia di ticket a più di un livello.';

    public function __construct(private readonly ?Ticket $ticket = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        $parent = Ticket::find($value);

        if ($parent === null) {
            return;
        }

        if ($parent->parent_id !== null) {
            $fail(self::MESSAGE);

            return;
        }

        if ($this->ticket !== null && $this->ticket->exists && $this->ticket->children()->exists()) {
            $fail(self::MESSAGE);
        }
    }
}
