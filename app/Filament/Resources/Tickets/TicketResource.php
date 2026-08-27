<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tickets;

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Queries\ProblemTicketsQuery;
use App\Domain\Ticketing\Queries\ToTestByMeQuery;
use App\Domain\Ticketing\Queries\WaitingQuery;
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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

    /**
     * Ricerca globale (US-603, §8.7): keyword-based su id/titolo/richiedente/corpo
     * messaggi. Nessun override di `getGlobalSearchEloquentQuery()` — il default di
     * Filament delega a `getEloquentQuery()` sopra, quindi lo scoping per Policy
     * (`Ticket::scopeVisibleTo()`) si applica automaticamente anche qui: un cliente
     * non trova mai ticket di altri nei risultati.
     *
     * @return array<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['id', 'title', 'requester.name', 'requester.email', 'messages.body_text'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        /** @var Ticket $record */
        return "#{$record->id} — {$record->title}";
    }

    /**
     * @return array<string, string>
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Ticket $record */
        return array_filter([
            'Richiedente' => $record->requester?->name,
            'Stato' => $record->status->getLabel(),
        ]);
    }

    public static function getGlobalSearchResultUrl(Model $record): ?string
    {
        /** @var Ticket $record */
        return static::canView($record) ? static::getUrl('view', ['record' => $record]) : null;
    }

    /**
     * Badge di navigazione (US-604, §8.4): un'unica voce di menu esiste per i ticket
     * (US-110, AC #1: nessuna sottoclasse per filtro), quindi i tre conteggi rilevanti
     * ("In attesa"/"Problemi"/"Da testare", già tab della tabella — {@see ListTickets})
     * sono combinati in un unico badge con tooltip di dettaglio, invece di tre voci di
     * menu distinte che l'architettura a Resource singola non prevede.
     */
    public static function getNavigationBadge(): ?string
    {
        $total = array_sum(self::navigationBadgeCounts());

        return $total > 0 ? (string) $total : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $counts = self::navigationBadgeCounts();

        return match (true) {
            $counts['problems'] > 0 => 'danger',
            $counts['waiting'] > 0 => 'warning',
            $counts['to_test'] > 0 => 'info',
            default => null,
        };
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        $counts = self::navigationBadgeCounts();

        return sprintf(
            '%d in attesa · %d in problema · %d da testare',
            $counts['waiting'],
            $counts['problems'],
            $counts['to_test'],
        );
    }

    /**
     * Un'unica chiamata a cache per i tre conteggi (mai una query per voce a ogni
     * render, §8.4): TTL breve configurabile (`ticketing.navigation_badges.
     * cache_ttl_seconds`), chiave scoped sull'utente autenticato ("Da testare" dipende
     * da `tester_id`, gli altri due da `Ticket::scopeVisibleTo()`).
     *
     * @return array{waiting: int, problems: int, to_test: int}
     */
    private static function navigationBadgeCounts(): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return ['waiting' => 0, 'problems' => 0, 'to_test' => 0];
        }

        /** @var array{waiting: int, problems: int, to_test: int} */
        return Cache::remember(
            "ticket-navigation-badge-counts:{$user->id}",
            config('ticketing.navigation_badges.cache_ttl_seconds'),
            fn (): array => [
                'waiting' => WaitingQuery::for($user)->count(),
                'problems' => ProblemTicketsQuery::for($user)->count(),
                'to_test' => ToTestByMeQuery::for($user)->count(),
            ],
        );
    }
}
