<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tickets;

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Models\Ticket;
use App\Filament\Resources\Tickets\Pages\CreateTicket;
use App\Filament\Resources\Tickets\Pages\EditTicket;
use App\Filament\Resources\Tickets\Pages\ListTickets;
use App\Filament\Resources\Tickets\Pages\ViewTicket;
use App\Filament\Resources\Tickets\Schemas\TicketForm;
use App\Filament\Resources\Tickets\Schemas\TicketInfolist;
use App\Filament\Resources\Tickets\Tables\TicketsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Unica Filament Resource per i ticket (US-110, AC #1: nessuna sottoclasse per
 * filtro). Policy-backed (`TicketPolicy`, US-105): nessun override manuale di
 * `can*()`, a differenza della `RoleResource` di sola lettura (che non ha una
 * Policy). `getEloquentQuery()` incatena SEMPRE `Ticket::scopeVisibleTo()`
 * (US-105, §9.5): protegge sia la tabella sia il binding di rotta per
 * View/Edit (`getRecordRouteBindingEloquentQuery()` di Filament riusa
 * `getEloquentQuery()` di default, non serve un secondo override).
 *
 * @extends resource<Ticket>
 */
class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static ?string $modelLabel = 'ticket';

    protected static ?string $pluralModelLabel = 'ticket';

    /**
     * Gruppo dinamico (US-602): un customer vede questa risorsa sotto "Area
     * cliente" insieme a Dashboard/Report/Documentazione/Fundraising —
     * staff (admin/manager/developer) resta sotto "Ticket" come prima.
     */
    public static function getNavigationGroup(): string|UnitEnum|null
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasRole(UserRole::Customer->value)
            ? 'Area cliente'
            : 'Ticket';
    }

    public static function form(Schema $schema): Schema
    {
        return TicketForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TicketInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TicketsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTickets::route('/'),
            'create' => CreateTicket::route('/create'),
            'view' => ViewTicket::route('/{record}'),
            'edit' => EditTicket::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<Ticket>
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = Auth::user();

        if (! $user instanceof User) {
            return $query->whereRaw('1 = 0');
        }

        return $query->visibleTo($user);
    }
}
