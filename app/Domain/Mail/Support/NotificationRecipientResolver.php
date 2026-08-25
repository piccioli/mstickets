<?php

declare(strict_types=1);

namespace App\Domain\Mail\Support;

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Mail\Enums\NotificationRecipientRole;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\StateMachine\TicketStateMachine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Tabella esplicita "attore che ha eseguito l'azione × transizione →
 * destinatari" (US-318, §7.5.3 del PRD, corregge il bug di precedenza
 * operatori `$a && $b && $c || $d` del v1, problema 12): una lista ORDINATA
 * di righe `from`/`to`/`roles`, mai un'espressione booleana. Ogni riga
 * riproduce alla lettera la colonna "Effetti" (voce "notifica X") della
 * macchina a stati di §6.1.3 — una transizione assente da questa tabella non
 * genera nessuna notifica di cambio stato, per design.
 *
 * `resolve()` cerca la PRIMA riga il cui `from` contiene lo stato di
 * partenza (o `null`, cioè "qualsiasi") e il cui `to` combacia: stesso
 * principio di precedenza già usato da
 * {@see TicketStateMachine::findTransition()}
 * (le righe più specifiche vanno elencate prima del catch-all). Il
 * principio "nessuno riceve la notifica di un'azione che ha eseguito lui
 * stesso" è applicato qui una sola volta, alla fine, per qualunque riga.
 */
final class NotificationRecipientResolver
{
    /**
     * @return Collection<int, User>
     */
    public static function resolve(Ticket $ticket, TicketStatus $from, TicketStatus $to, User $actor): Collection
    {
        $rule = self::firstMatchingRule($from, $to);

        if ($rule === null) {
            return collect();
        }

        return collect($rule['roles'])
            ->flatMap(fn (NotificationRecipientRole $role): Collection => self::usersForRole($ticket, $role))
            ->reject(fn (User $user): bool => $user->is($actor))
            ->unique('id')
            ->values();
    }

    /**
     * @return array{from: ?list<TicketStatus>, to: TicketStatus, roles: list<NotificationRecipientRole>}|null
     */
    private static function firstMatchingRule(TicketStatus $from, TicketStatus $to): ?array
    {
        foreach (self::table() as $rule) {
            if ($rule['to'] !== $to) {
                continue;
            }

            if ($rule['from'] === null || in_array($from, $rule['from'], strict: true)) {
                return $rule;
            }
        }

        return null;
    }

    /**
     * Righe in ordine di precedenza (più specifiche prima). Riproduce
     * esattamente la colonna "Effetti" di §6.1.3: nessuna riga qui non
     * corrispondente a un "notifica X" scritto in quella tabella.
     *
     * @return list<array{from: ?list<TicketStatus>, to: TicketStatus, roles: list<NotificationRecipientRole>}>
     */
    private static function table(): array
    {
        return [
            // testing -> rejected: notifica assegnatario + richiedente (prevale sul catch-all sotto).
            [
                'from' => [TicketStatus::Testing],
                'to' => TicketStatus::Rejected,
                'roles' => [NotificationRecipientRole::Assignee, NotificationRecipientRole::Requester],
            ],
            // new -> rejected: notifica richiedente (prevale sul catch-all sotto).
            [
                'from' => [TicketStatus::New],
                'to' => TicketStatus::Rejected,
                'roles' => [NotificationRecipientRole::Requester],
            ],
            // progress -> testing: notifica tester.
            [
                'from' => [TicketStatus::Progress],
                'to' => TicketStatus::Testing,
                'roles' => [NotificationRecipientRole::Tester],
            ],
            // testing -> tested: notifica assegnatario.
            [
                'from' => [TicketStatus::Testing],
                'to' => TicketStatus::Tested,
                'roles' => [NotificationRecipientRole::Assignee],
            ],
            // testing -> todo (test fallito): notifica assegnatario.
            [
                'from' => [TicketStatus::Testing],
                'to' => TicketStatus::Todo,
                'roles' => [NotificationRecipientRole::Assignee],
            ],
            // {new,backlog,assigned,todo,progress} -> waiting: notifica richiedente.
            [
                'from' => [
                    TicketStatus::New,
                    TicketStatus::Backlog,
                    TicketStatus::Assigned,
                    TicketStatus::Todo,
                    TicketStatus::Progress,
                ],
                'to' => TicketStatus::Waiting,
                'roles' => [NotificationRecipientRole::Requester],
            ],
            // {new,backlog,assigned,todo,progress} -> problem: notifica manager.
            [
                'from' => [
                    TicketStatus::New,
                    TicketStatus::Backlog,
                    TicketStatus::Assigned,
                    TicketStatus::Todo,
                    TicketStatus::Progress,
                ],
                'to' => TicketStatus::Problem,
                'roles' => [NotificationRecipientRole::Manager],
            ],
            // qualsiasi altro -> rejected: catch-all, notifica richiedente.
            [
                'from' => null,
                'to' => TicketStatus::Rejected,
                'roles' => [NotificationRecipientRole::Requester],
            ],
        ];
    }

    /**
     * @return Collection<int, User>
     */
    private static function usersForRole(Ticket $ticket, NotificationRecipientRole $role): Collection
    {
        return match ($role) {
            NotificationRecipientRole::Requester => collect(array_filter([$ticket->requester])),
            NotificationRecipientRole::Assignee => collect(array_filter([$ticket->assignee])),
            NotificationRecipientRole::Tester => collect(array_filter([$ticket->tester])),
            NotificationRecipientRole::Manager => User::query()
                ->active()
                ->whereHas(
                    'roles',
                    fn (Builder $query): Builder => $query->where('name', UserRole::Manager->value),
                )
                ->get(),
        };
    }
}
