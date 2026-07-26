<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\StateMachine;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Rules\TicketProblemReasonRequiredRule;
use App\Domain\Ticketing\Rules\TicketTesterRequiredRule;
use App\Domain\Ticketing\Rules\TicketWaitingReasonRequiredRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Validation\ValidationException;

/**
 * Macchina a stati dichiarativa del ticket (PRD §6.1.3, principio architetturale A2).
 *
 * Tutta la logica di transizione vive in {@see self::transitions()}: nessun `if` sparso
 * altrove. Copre le transizioni "manuali" del percorso principale/senza-testing,
 * waiting/problem e il relativo ripristino, e il catch-all verso `rejected`. ESCLUDE
 * deliberatamente le righe riservate a comandi schedulati non ancora esistenti (§6.1.5):
 * T3 (`tickets:progress-to-todo`), T4 (`tickets:auto-close-released`), T5
 * (`tickets:close-scrum`, riga `* → done` guardata da `type = scrum`) — arrivano in
 * Fase 6 insieme ai comandi artisan corrispondenti.
 *
 * Decisione Q4: nessuna riga per T2 (azzeramento di `assignee_id` su cambio di stato
 * manuale di un ticket `new`) — comportamento esplicitamente diverso dal v1.
 *
 * Questa classe è invocata solo da un'Action esplicita (`ChangeTicketStatus`, US-103):
 * nessun hook Eloquent la richiama (A1).
 */
final class TicketStateMachine
{
    /**
     * @return list<Transition>
     */
    public static function transitions(): array
    {
        static $transitions = null;

        if ($transitions !== null) {
            return $transitions;
        }

        $anyOtherStatus = array_values(array_filter(
            TicketStatus::cases(),
            static fn (TicketStatus $status): bool => ! in_array($status, [
                TicketStatus::New,
                TicketStatus::Testing,
                TicketStatus::Done,
                TicketStatus::Rejected,
            ], strict: true),
        ));

        return $transitions = [
            new Transition(
                from: [TicketStatus::New],
                to: TicketStatus::Assigned,
                actors: [TransitionActor::AdminOrManager, TransitionActor::AutoAssigningDeveloper],
                guard: self::guardAssigneeValued(...),
                guardMessage: 'La transizione richiede di specificare un assegnatario.',
            ),
            new Transition(
                from: [TicketStatus::New],
                to: TicketStatus::Backlog,
                actors: [TransitionActor::AdminOrManager, TransitionActor::NoRelationRequired],
            ),
            new Transition(
                from: [TicketStatus::New],
                to: TicketStatus::Rejected,
                actors: [TransitionActor::AdminOrManager],
            ),
            new Transition(
                from: [TicketStatus::Backlog],
                to: TicketStatus::Assigned,
                actors: [TransitionActor::AdminOrManager, TransitionActor::AutoAssigningDeveloper],
                guard: self::guardAssigneeValued(...),
                guardMessage: 'La transizione richiede di specificare un assegnatario.',
            ),
            new Transition(
                from: [TicketStatus::Backlog],
                to: TicketStatus::Todo,
                actors: [TransitionActor::AdminOrManager, TransitionActor::AutoAssigningDeveloper],
                guard: self::guardAssigneeValued(...),
                guardMessage: 'La transizione richiede di specificare un assegnatario.',
            ),
            new Transition(
                from: [TicketStatus::Assigned],
                to: TicketStatus::Todo,
                actors: [TransitionActor::AdminOrManager, TransitionActor::Assignee],
            ),
            new Transition(
                from: [TicketStatus::Todo],
                to: TicketStatus::Progress,
                actors: [TransitionActor::AdminOrManager, TransitionActor::Assignee],
                effects: [TransitionEffect::DemoteOtherProgressTickets],
            ),
            new Transition(
                from: [TicketStatus::Progress],
                to: TicketStatus::Testing,
                actors: [TransitionActor::AdminOrManager, TransitionActor::Assignee],
                guard: self::guardTesterValued(...),
                guardMessage: TicketTesterRequiredRule::MESSAGE,
            ),
            new Transition(
                from: [TicketStatus::Progress],
                to: TicketStatus::Released,
                actors: [TransitionActor::AdminOrManager, TransitionActor::Assignee],
                effects: [TransitionEffect::SetReleasedAt],
            ),
            new Transition(
                from: [TicketStatus::Progress],
                to: TicketStatus::Todo,
                actors: [TransitionActor::AdminOrManager, TransitionActor::Assignee],
            ),
            new Transition(
                from: [TicketStatus::Testing],
                to: TicketStatus::Tested,
                actors: [TransitionActor::AdminOrManager, TransitionActor::Tester],
            ),
            new Transition(
                from: [TicketStatus::Testing],
                to: TicketStatus::Todo,
                actors: [TransitionActor::AdminOrManager, TransitionActor::Tester],
            ),
            new Transition(
                from: [TicketStatus::Testing],
                to: TicketStatus::Rejected,
                actors: [TransitionActor::AdminOrManager, TransitionActor::Tester],
            ),
            new Transition(
                from: [TicketStatus::Tested],
                to: TicketStatus::Released,
                actors: [TransitionActor::AdminOrManager, TransitionActor::Assignee],
                effects: [TransitionEffect::SetReleasedAt],
            ),
            new Transition(
                from: [TicketStatus::Released],
                to: TicketStatus::Done,
                actors: [TransitionActor::AdminOrManager, TransitionActor::Assignee],
                effects: [TransitionEffect::SetDoneAt],
            ),
            new Transition(
                from: [
                    TicketStatus::New,
                    TicketStatus::Backlog,
                    TicketStatus::Assigned,
                    TicketStatus::Todo,
                    TicketStatus::Progress,
                ],
                to: TicketStatus::Waiting,
                actors: [TransitionActor::AdminOrManager, TransitionActor::Assignee],
                guard: self::guardWaitingReasonValued(...),
                guardMessage: TicketWaitingReasonRequiredRule::MESSAGE,
                effects: [TransitionEffect::SavePreviousStatus],
            ),
            new Transition(
                from: [
                    TicketStatus::New,
                    TicketStatus::Backlog,
                    TicketStatus::Assigned,
                    TicketStatus::Todo,
                    TicketStatus::Progress,
                ],
                to: TicketStatus::Problem,
                actors: [TransitionActor::AdminOrManager, TransitionActor::Assignee],
                guard: self::guardProblemReasonValued(...),
                guardMessage: TicketProblemReasonRequiredRule::MESSAGE,
                effects: [TransitionEffect::SavePreviousStatus],
            ),
            new Transition(
                from: [TicketStatus::Waiting],
                to: null,
                actors: [TransitionActor::AdminOrManager, TransitionActor::Assignee, TransitionActor::System],
                guard: self::guardPreviousStatusValued(...),
                guardMessage: 'Non c\'è uno stato precedente a cui tornare.',
                effects: [TransitionEffect::RestorePreviousStatus],
            ),
            new Transition(
                from: [TicketStatus::Problem],
                to: null,
                actors: [TransitionActor::AdminOrManager, TransitionActor::Assignee],
                guard: self::guardPreviousStatusValued(...),
                guardMessage: 'Non c\'è uno stato precedente a cui tornare.',
                effects: [TransitionEffect::RestorePreviousStatus],
            ),
            new Transition(
                from: $anyOtherStatus,
                to: TicketStatus::Rejected,
                actors: [TransitionActor::AdminOrManager],
            ),
        ];
    }

    /**
     * Verifica una transizione e lancia un errore di validazione localizzato (mai
     * un'eccezione generica, A2) se non è ammessa in tabella, se l'utente non è un
     * attore autorizzato, o se il guard non è soddisfatto.
     *
     * @param  array<string, mixed>  $context  Valori che l'Action chiamante sta per
     *                                         applicare contestualmente alla transizione
     *                                         (es. `assignee_id`, `tester_id`,
     *                                         `waiting_reason`, `problem_reason`).
     * @return Transition La riga di tabella risolta, così l'Action chiamante
     *                    (`ChangeTicketStatus`, US-103) può eseguirne gli `effects`
     *                    senza dover ripetere la ricerca.
     */
    public static function authorize(Ticket $ticket, TicketStatus $to, User $user, array $context = []): Transition
    {
        $from = $ticket->status;

        $transition = self::findTransition($ticket, $from, $to);

        if ($transition === null) {
            throw ValidationException::withMessages([
                'status' => ["La transizione da \"{$from->getLabel()}\" a \"{$to->getLabel()}\" non è ammessa."],
            ]);
        }

        if (! $transition->isAuthorizedFor($user, $ticket, $context)) {
            throw ValidationException::withMessages([
                'status' => ['Non hai i permessi per eseguire questa transizione su questo ticket.'],
            ]);
        }

        if (! $transition->guardPasses($ticket, $context)) {
            throw ValidationException::withMessages([
                'status' => [$transition->guardMessage ?? 'La transizione non soddisfa le condizioni richieste.'],
            ]);
        }

        return $transition;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function can(Ticket $ticket, TicketStatus $to, User $user, array $context = []): bool
    {
        try {
            self::authorize($ticket, $to, $user, $context);

            return true;
        } catch (ValidationException) {
            return false;
        }
    }

    private static function findTransition(Ticket $ticket, TicketStatus $from, TicketStatus $to): ?Transition
    {
        foreach (self::transitions() as $transition) {
            if ($transition->appliesTo($from) && $transition->matchesTarget($ticket, $to)) {
                return $transition;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function guardAssigneeValued(Ticket $ticket, array $context): bool
    {
        $value = array_key_exists('assignee_id', $context) ? $context['assignee_id'] : $ticket->assignee_id;

        return $value !== null;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function guardTesterValued(Ticket $ticket, array $context): bool
    {
        $value = array_key_exists('tester_id', $context) ? $context['tester_id'] : $ticket->tester_id;

        return self::passesRule($value, new TicketTesterRequiredRule);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function guardWaitingReasonValued(Ticket $ticket, array $context): bool
    {
        $value = array_key_exists('waiting_reason', $context) ? $context['waiting_reason'] : $ticket->waiting_reason;

        return self::passesRule($value, new TicketWaitingReasonRequiredRule);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function guardProblemReasonValued(Ticket $ticket, array $context): bool
    {
        $value = array_key_exists('problem_reason', $context) ? $context['problem_reason'] : $ticket->problem_reason;

        return self::passesRule($value, new TicketProblemReasonRequiredRule);
    }

    /**
     * Esegue una Validation Rule di dominio (US-102) su un singolo valore e ne riporta
     * l'esito come booleano, cortocircuitando il primo `$fail()`: le regole restano la
     * fonte unica di verità sia per il guard sia per qualunque form/API che le riusa.
     */
    private static function passesRule(mixed $value, ValidationRule $rule): bool
    {
        $failed = false;

        $rule->validate('value', $value, function (string $attribute, ?string $message = null) use (&$failed): PotentiallyTranslatedString {
            $failed = true;

            return new PotentiallyTranslatedString($message ?? $attribute, app('translator'));
        });

        return ! $failed;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function guardPreviousStatusValued(Ticket $ticket, array $context): bool
    {
        return $ticket->previous_status !== null;
    }
}
