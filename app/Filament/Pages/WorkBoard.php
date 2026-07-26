<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Enums\TicketStatus;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketLog;
use App\Filament\Resources\Tickets\Support\TicketFieldAccess;
use App\Filament\Resources\Tickets\Support\TicketLogFormatter;
use App\Filament\Resources\Tickets\TicketResource;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Vista di lavoro essenziale (§8.6, US-113): landing di admin/manager/developer
 * dopo il login (vedi {@see Dashboard}). Colonne per stato,
 * card minime, nessun cambio di stato diretto da qui — ogni card rimanda alla
 * pagina del ticket (US-110), dove le transizioni ammesse (US-101) sono già
 * implementate e testate: evita di duplicare `TicketTransitionActions` in un
 * secondo contesto (N ticket × M transizioni) per una "versione essenziale"
 * (rifinitura, incluso eventuale drag & drop, resta esplicitamente in Fase 6).
 */
class WorkBoard extends Page
{
    protected string $view = 'filament.pages.work-board';

    protected static ?string $title = 'Vista di lavoro';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedViewColumns;

    protected static string|UnitEnum|null $navigationGroup = 'Lavoro';

    protected static ?string $navigationLabel = 'Vista di lavoro';

    protected static ?int $navigationSort = -1;

    public ?int $assigneeId = null;

    public static function canAccess(): bool
    {
        return TicketFieldAccess::canManageInternalFields();
    }

    /**
     * Ticket visibili all'utente (+ filtro assegnatario), raggruppati per stato in
     * un'unica query (nessun N+1: una query per i ticket, una per ciascuna
     * relazione eager-caricata, indipendentemente dal numero di colonne/righe).
     *
     * @return array<string, Collection<int, Ticket>>
     */
    public function columns(): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return [];
        }

        $tickets = Ticket::query()
            ->visibleTo($user)
            ->when(
                $this->assigneeId !== null,
                fn (Builder $query): Builder => $query->where('assignee_id', $this->assigneeId),
            )
            ->with(['requester.organizations', 'tags'])
            ->orderByDesc('status_changed_at')
            ->get()
            ->groupBy(fn (Ticket $ticket): string => $ticket->status->value);

        return collect(TicketStatus::cases())
            ->mapWithKeys(fn (TicketStatus $status): array => [
                $status->value => $tickets->get($status->value) ?? collect(),
            ])
            ->all();
    }

    /**
     * Solo admin/manager/developer possono essere selezionati (§6.1.3, unici
     * assegnatari possibili): stessa condizione di {@see TicketFieldAccess}, qui
     * espressa via ruolo perché serve elencare gli utenti, non un singolo check.
     *
     * @return array<int, string>
     */
    public function assigneeOptions(): array
    {
        return User::query()
            ->active()
            ->whereHas(
                'roles',
                fn (Builder $query): Builder => $query->whereIn('name', [
                    UserRole::Admin->value,
                    UserRole::Manager->value,
                    UserRole::Developer->value,
                ]),
            )
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function clientName(Ticket $ticket): string
    {
        $requester = $ticket->requester;

        if ($requester === null) {
            return '—';
        }

        $organization = $requester->organizations->first();

        return $organization !== null ? $organization->name : $requester->name;
    }

    public function ticketUrl(Ticket $ticket): string
    {
        return TicketResource::getUrl('view', ['record' => $ticket]);
    }

    public function describeLog(TicketLog $log): string
    {
        return TicketLogFormatter::describe($log);
    }

    /**
     * @return EloquentCollection<int, TicketLog>
     */
    public function recentActivity(): EloquentCollection
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return new EloquentCollection;
        }

        $visibleTicketIds = Ticket::query()->visibleTo($user)->pluck('id');

        return TicketLog::query()
            ->whereIn('ticket_id', $visibleTicketIds)
            ->with(['ticket', 'user'])
            ->orderByDesc('occurred_at')
            ->limit(20)
            ->get();
    }
}
