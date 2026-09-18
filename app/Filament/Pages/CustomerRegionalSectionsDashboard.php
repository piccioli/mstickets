<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Identity\Queries\SectionsInRegionQuery;
use App\Domain\Ticketing\Queries\MyTicketsQuery;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Elenco delle Sezioni della propria regione per un cliente Gruppo Regionale (US-705/US-807):
 * prima era una card dentro {@see CustomerDashboard} ("Sezioni del gruppo regionale"), ora ha
 * una voce di navigazione propria sotto il gruppo "GR" — la dashboard cliente generica
 * ({@see CustomerDashboard::getNavigationGroup()}) non la mostra più per un Gruppo Regionale.
 * Nessun'altra novità: stessa query/stesso conteggio ticket/stesso link al dettaglio
 * {@see CaiSectionRegionalDetail} di prima, solo spostati qui.
 */
class CustomerRegionalSectionsDashboard extends Page
{
    protected string $view = 'filament.pages.customer-regional-sections-dashboard';

    protected static ?string $title = 'Dashboard';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static string|UnitEnum|null $navigationGroup = 'Sezioni';

    protected static ?string $navigationLabel = 'Dashboard';

    /**
     * Stesso gate di {@see CaiSectionRegionalDetail::canAccess()}: solo un cliente Gruppo
     * Regionale, mai una Sezione/Organo Tecnico/Generico.
     */
    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $user->hasRole(UserRole::Customer->value)
            && $user->customer_type === CustomerType::GruppoRegionale;
    }

    /**
     * Sezioni della stessa regione del Gruppo Regionale corrente (US-705). Stato vuoto esplicito
     * (mai un errore) sia quando la regione non ha ancora nessuna sezione classificata, sia quando
     * il Gruppo Regionale ha `region = null`.
     *
     * @return EloquentCollection<int, User>
     */
    public function regionalGroupSections(): EloquentCollection
    {
        $user = Auth::user();

        if (! $user instanceof User || $user->customer_type !== CustomerType::GruppoRegionale || $user->region === null) {
            return new EloquentCollection;
        }

        return SectionsInRegionQuery::for($user->region)->get();
    }

    /**
     * Conteggio ticket aperti di una Sezione elencata in questa pagina: riusa
     * {@see MyTicketsQuery} passando la Sezione stessa (non l'utente autenticato) — il suo
     * unico permesso `ticket.view.own` scopa comunque il risultato ai propri ticket, quindi il
     * conteggio resta corretto senza duplicare la regola "aperti = non Done/Rejected".
     */
    public function sectionOpenTicketsCount(User $section): int
    {
        return MyTicketsQuery::for($section)->count();
    }

    /**
     * URL della pagina di dettaglio CAI/RUNTS di una Sezione elencata in questa pagina (US-807,
     * {@see CaiSectionRegionalDetail}) — l'autorizzazione sulla singola sezione (deve appartenere
     * alla propria regione) è verificata lato server in {@see CaiSectionRegionalDetail::mount()},
     * non solo dall'assenza del link in UI.
     */
    public function sectionDetailUrl(User $section): string
    {
        return CaiSectionRegionalDetail::getUrl(['record' => $section->id]);
    }
}
