<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tickets\Pages;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Queries\ActiveRequestsQuery;
use App\Domain\Ticketing\Queries\AllCustomerTicketsQuery;
use App\Domain\Ticketing\Queries\ArchivedTicketsQuery;
use App\Domain\Ticketing\Queries\AssignedToMeQuery;
use App\Domain\Ticketing\Queries\BacklogQuery;
use App\Domain\Ticketing\Queries\InProgressTicketsQuery;
use App\Domain\Ticketing\Queries\InternalTicketsQuery;
use App\Domain\Ticketing\Queries\InTestingQuery;
use App\Domain\Ticketing\Queries\MyArchivedTicketsQuery;
use App\Domain\Ticketing\Queries\MyTicketsQuery;
use App\Domain\Ticketing\Queries\NewTicketsQuery;
use App\Domain\Ticketing\Queries\ProblemTicketsQuery;
use App\Domain\Ticketing\Queries\ToTestByMeQuery;
use App\Domain\Ticketing\Queries\WaitingQuery;
use App\Filament\Resources\Tickets\Support\TicketFieldAccess;
use App\Filament\Resources\Tickets\TicketResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Le viste di §8.5 (US-111) sono tab della tabella: ogni tab delega interamente il
 * filtro al query object corrispondente in `App\Domain\Ticketing\Queries`, mai una
 * condizione scritta qui. Il set di tab dipende dal ruolo (staff vs cliente,
 * {@see TicketFieldAccess::canManageInternalFields()}, stesso gate di US-110): un
 * cliente non vede mai le tab riservate allo staff. Ogni query object incatena già
 * {@see Ticket::scopeVisibleTo()}, quindi il risultato
 * resta corretto anche se `getEloquentQuery()` della resource cambiasse in futuro.
 */
class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return [];
        }

        if (! TicketFieldAccess::canManageInternalFields()) {
            return [
                'my_tickets' => Tab::make('I miei ticket')
                    ->modifyQueryUsing(fn (): Builder => MyTicketsQuery::for($user)),
                'archive' => Tab::make('Archivio')
                    ->modifyQueryUsing(fn (): Builder => MyArchivedTicketsQuery::for($user)),
            ];
        }

        return [
            'all' => Tab::make('Tutti i ticket'),
            'active_requests' => Tab::make('Richieste attive')
                ->modifyQueryUsing(fn (): Builder => ActiveRequestsQuery::for($user)),
            'customer_tickets' => Tab::make('Tutti i ticket di clienti')
                ->modifyQueryUsing(fn (): Builder => AllCustomerTicketsQuery::for($user)),
            'new' => Tab::make('Nuovi')
                ->modifyQueryUsing(fn (): Builder => NewTicketsQuery::for($user)),
            'in_progress' => Tab::make('In lavorazione')
                ->modifyQueryUsing(fn (): Builder => InProgressTicketsQuery::for($user)),
            'assigned_to_me' => Tab::make('Assegnati a me')
                ->modifyQueryUsing(fn (): Builder => AssignedToMeQuery::for($user)),
            'to_test_by_me' => Tab::make('Da testare')
                ->modifyQueryUsing(fn (): Builder => ToTestByMeQuery::for($user)),
            'in_testing' => Tab::make('In test')
                ->modifyQueryUsing(fn (): Builder => InTestingQuery::for($user)),
            'waiting' => Tab::make('In attesa')
                ->modifyQueryUsing(fn (): Builder => WaitingQuery::for($user)),
            'problems' => Tab::make('Problemi')
                ->modifyQueryUsing(fn (): Builder => ProblemTicketsQuery::for($user)),
            'backlog' => Tab::make('Backlog')
                ->modifyQueryUsing(fn (): Builder => BacklogQuery::for($user)),
            'archive' => Tab::make('Archivio')
                ->modifyQueryUsing(fn (): Builder => ArchivedTicketsQuery::for($user)),
            'internal' => Tab::make('Interni')
                ->modifyQueryUsing(fn (): Builder => InternalTicketsQuery::for($user)),
        ];
    }
}
