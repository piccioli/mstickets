<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\DTO\ApplyStatusToChildrenResult;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use Illuminate\Validation\ValidationException;

/**
 * DECISIONE Q5 del PRD: il cambio di stato di un ticket padre non si propaga MAI
 * automaticamente ai figli (comportamento diverso dal v1, che lo faceva in cascata).
 * Questa Action è l'UNICO modo per propagare esplicitamente lo stesso cambio di stato
 * ai figli diretti: va invocata a parte da {@see ChangeTicketStatus} (US-103), che non
 * la richiama mai implicitamente.
 *
 * Ogni figlio è valutato in isolamento tramite {@see ChangeTicketStatus}, che applica
 * le stesse regole di transizione/guard di US-101: un figlio per cui la transizione non
 * è ammessa viene saltato (riportato in `skipped` con il motivo) senza bloccare gli
 * altri, e senza scrivere nulla per quel figlio (coerente con US-103).
 */
final class ApplyStatusToChildren
{
    /**
     * @param  array<string, mixed>  $context  Passato invariato a ogni figlio, come per
     *                                         la transizione del padre.
     */
    public static function run(Ticket $parent, TicketStatus $to, User $user, array $context = []): ApplyStatusToChildrenResult
    {
        $applied = [];
        $skipped = [];

        foreach ($parent->children as $child) {
            try {
                $applied[] = ChangeTicketStatus::run($child, $to, $user, $context);
            } catch (ValidationException $exception) {
                $skipped[] = [
                    'ticket' => $child,
                    'reason' => $exception->errors()['status'][0] ?? $exception->getMessage(),
                ];
            }
        }

        return new ApplyStatusToChildrenResult($applied, $skipped);
    }
}
