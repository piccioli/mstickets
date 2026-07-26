<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tickets\Support;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Actions\ApplyStatusToChildren;
use App\Domain\Ticketing\Actions\ChangeTicketStatus;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\StateMachine\TicketStateMachine;
use App\Domain\Ticketing\StateMachine\Transition;
use App\Domain\Ticketing\StateMachine\TransitionActor;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Costruisce dinamicamente i bottoni di transizione di stato del ticket (US-110,
 * §6.1.3): per il ticket corrente e l'utente autenticato, itera
 * `TicketStatus::cases()` e usa `TicketStateMachine::can()` (che invoca
 * `authorize()`, US-101) per decidere quali target mostrare come `Action` — mai
 * un'edizione diretta della colonna `status` da form. Riusata sia da `ViewTicket`
 * sia da `EditTicket` (header actions su entrambe le pagine, per AC), così la
 * logica di costruzione non è duplicata tra le due.
 */
final class TicketTransitionActions
{
    /**
     * @return list<Action>
     */
    public static function build(Ticket $ticket): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return [];
        }

        $actions = [];

        foreach (TicketStatus::cases() as $target) {
            if ($target === $ticket->status) {
                continue;
            }

            $transition = self::resolveTransition($ticket, $target);

            if ($transition === null) {
                continue;
            }

            $autoAssignsSelf = in_array(TransitionActor::AutoAssigningDeveloper, $transition->actors, true)
                && ! $user->can(Permission::TicketTransitionAny);

            $baseContext = $autoAssignsSelf ? ['assignee_id' => $user->id] : [];

            // Solo attore/tabella, MAI il guard: i guard "campo valorizzato"
            // (tester/waiting_reason/problem_reason/assignee_id) dipendono da un
            // valore che l'utente fornisce nel modale dell'action stessa, quindi
            // non possono ancora essere soddisfatti quando si decide se MOSTRARE
            // il bottone. Il guard resta comunque verificato per davvero al submit
            // da `ChangeTicketStatus::run()` (via `authorize()`): un fallimento a
            // quel punto produce la notifica di errore, non un bottone mancante.
            if (! $transition->isAuthorizedFor($user, $ticket, $baseContext)) {
                continue;
            }

            $actions[] = self::buildAction($ticket, $target, $autoAssignsSelf);
        }

        return $actions;
    }

    private static function resolveTransition(Ticket $ticket, TicketStatus $to): ?Transition
    {
        foreach (TicketStateMachine::transitions() as $transition) {
            if ($transition->appliesTo($ticket->status) && $transition->matchesTarget($ticket, $to)) {
                return $transition;
            }
        }

        return null;
    }

    /**
     * Il guard "assignee_id valorizzato" (US-101) si applica a `new`/`backlog` →
     * `assigned`/`todo`: se il ticket non ha già un assegnatario e l'attore non sta
     * auto-assegnandosi silenziosamente, l'action deve chiedere chi assegnare.
     */
    private static function requiresAssigneeField(Ticket $ticket, TicketStatus $to, bool $autoAssignsSelf): bool
    {
        return ! $autoAssignsSelf
            && in_array($to, [TicketStatus::Assigned, TicketStatus::Todo], true)
            && $ticket->assignee_id === null;
    }

    private static function buildAction(Ticket $ticket, TicketStatus $to, bool $autoAssignsSelf): Action
    {
        $needsAssigneeField = self::requiresAssigneeField($ticket, $to, $autoAssignsSelf);
        $needsTesterField = $to === TicketStatus::Testing;
        $needsWaitingReason = $to === TicketStatus::Waiting;
        $needsProblemReason = $to === TicketStatus::Problem;

        $schema = [];

        if ($needsAssigneeField) {
            $schema[] = Select::make('assignee_id')
                ->label('Assegnatario')
                ->options(fn () => User::query()->active()->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->required();
        }

        if ($needsTesterField) {
            $schema[] = Select::make('tester_id')
                ->label('Tester')
                ->options(fn () => User::query()->active()->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->required();
        }

        if ($needsWaitingReason) {
            $schema[] = Textarea::make('waiting_reason')
                ->label('Motivo dell\'attesa')
                ->required();
        }

        if ($needsProblemReason) {
            $schema[] = Textarea::make('problem_reason')
                ->label('Motivo del blocco')
                ->required();
        }

        $schema[] = Checkbox::make('apply_to_children')
            ->label('Applica anche ai ticket figli')
            ->default(false);

        return Action::make('transition_'.$to->value)
            ->label($to->getLabel())
            ->icon($to->getIcon())
            ->color($to->getColor())
            ->modalHeading('Cambia stato in "'.$to->getLabel().'"')
            ->schema($schema)
            ->action(function (array $data) use ($ticket, $to, $autoAssignsSelf): void {
                $user = Auth::user();

                if (! $user instanceof User) {
                    return;
                }

                $context = $autoAssignsSelf ? ['assignee_id' => $user->id] : [];

                if (array_key_exists('assignee_id', $data) && $data['assignee_id'] !== null) {
                    $context['assignee_id'] = (int) $data['assignee_id'];
                }

                if (array_key_exists('tester_id', $data) && $data['tester_id'] !== null) {
                    $context['tester_id'] = (int) $data['tester_id'];
                }

                if (array_key_exists('waiting_reason', $data)) {
                    $context['waiting_reason'] = $data['waiting_reason'];
                }

                if (array_key_exists('problem_reason', $data)) {
                    $context['problem_reason'] = $data['problem_reason'];
                }

                try {
                    ChangeTicketStatus::run($ticket, $to, $user, $context);
                } catch (ValidationException $exception) {
                    Notification::make()
                        ->danger()
                        ->title('Transizione non riuscita')
                        ->body($exception->errors()['status'][0] ?? $exception->getMessage())
                        ->send();

                    return;
                }

                if (($data['apply_to_children'] ?? false) === true) {
                    self::applyToChildrenAndNotify($ticket, $to, $user, $context);
                }

                Notification::make()
                    ->success()
                    ->title('Stato del ticket aggiornato')
                    ->send();
            });
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function applyToChildrenAndNotify(Ticket $ticket, TicketStatus $to, User $user, array $context): void
    {
        $result = ApplyStatusToChildren::run($ticket->fresh() ?? $ticket, $to, $user, $context);

        if ($result->skipped === []) {
            return;
        }

        $reasons = collect($result->skipped)
            ->map(fn (array $skip): string => "#{$skip['ticket']->id} {$skip['ticket']->title}: {$skip['reason']}")
            ->implode(' — ');

        Notification::make()
            ->warning()
            ->title('Alcuni ticket figli sono stati saltati')
            ->body($reasons)
            ->send();
    }
}
