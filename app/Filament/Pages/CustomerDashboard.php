<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\Documentation\Models\DocumentationPage;
use App\Domain\Fundraising\Models\FundraisingProject;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Reporting\Models\ActivityReport;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Queries\MyTicketsAwaitingResponseQuery;
use App\Domain\Ticketing\Queries\MyTicketsQuery;
use App\Filament\Resources\ActivityReports\ActivityReportResource;
use App\Filament\Resources\CustomerFundraisingProjects\CustomerFundraisingProjectResource;
use App\Filament\Resources\DocumentationPages\DocumentationPageResource;
use App\Filament\Resources\Tickets\TicketResource;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * Dashboard cliente (US-601): panoramica dell'attività del SOLO utente
 * autenticato (mai un elenco generico) — ticket aperti, ticket che
 * richiedono una sua risposta, documentazione customer recente, link
 * `drive_url`/`drive_budget_url` propri, propri report attività (Fase 4),
 * progetti fundraising in cui è coinvolto (Fase 5). Nessun riferimento a
 * chat di supporto (`help_desk_chat_url` non confermato dal committente,
 * fuori scope, §PRD Fase 6).
 *
 * Il redirect di ruolo su {@see Dashboard::mount()} è US-602, che raggruppa
 * anche questa pagina sotto "Area cliente" in navigazione —
 * {@see self::canAccess()} resta comunque il gate reale per l'accesso
 * diretto via URL.
 *
 * `canAccess()` riusa lo stesso idioma già in uso altrove nel dominio Mail
 * (`$user->hasRole(UserRole::Customer->value)`, es.
 * `SendNewCustomerTicketStaffMail`) per riconoscere "è un customer": a
 * differenza di `TicketFieldAccess::canManageInternalFields()` (un permesso
 * "ombrello" per "è staff"), qui serve l'esatto contrario — un ruolo
 * specifico, non un permesso, perché questa pagina non corrisponde a un
 * singolo permesso del catalogo ma a un intero ruolo applicativo.
 */
class CustomerDashboard extends Page
{
    protected string $view = 'filament.pages.customer-dashboard';

    protected static ?string $title = 'Dashboard';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static string|UnitEnum|null $navigationGroup = 'Area cliente';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = -2;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasRole(UserRole::Customer->value);
    }

    public function openTicketsCount(): int
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return 0;
        }

        return MyTicketsQuery::for($user)->count();
    }

    public function openTicketsUrl(): string
    {
        return TicketResource::getUrl('index', ['tab' => 'my_tickets']);
    }

    /**
     * @return EloquentCollection<int, Ticket>
     */
    public function ticketsAwaitingResponse(): EloquentCollection
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return new EloquentCollection;
        }

        return MyTicketsAwaitingResponseQuery::for($user)
            ->orderBy('status_changed_at')
            ->get();
    }

    public function ticketUrl(Ticket $ticket): string
    {
        return TicketResource::getUrl('view', ['record' => $ticket]);
    }

    /**
     * @return EloquentCollection<int, DocumentationPage>
     */
    public function recentDocumentation(): EloquentCollection
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return new EloquentCollection;
        }

        return DocumentationPage::query()
            ->visibleTo($user)
            ->latest()
            ->limit(5)
            ->get();
    }

    public function documentationUrl(): string
    {
        return DocumentationPageResource::getUrl('index');
    }

    public function driveUrl(): ?string
    {
        $user = Auth::user();

        return $user instanceof User && filled($user->drive_url) ? $user->drive_url : null;
    }

    public function driveBudgetUrl(): ?string
    {
        $user = Auth::user();

        return $user instanceof User && filled($user->drive_budget_url) ? $user->drive_budget_url : null;
    }

    /**
     * @return EloquentCollection<int, ActivityReport>
     */
    public function ownActivityReports(): EloquentCollection
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return new EloquentCollection;
        }

        return ActivityReport::query()
            ->visibleTo($user)
            ->latest()
            ->limit(5)
            ->get();
    }

    public function activityReportsUrl(): string
    {
        return ActivityReportResource::getUrl('index');
    }

    /**
     * @return EloquentCollection<int, FundraisingProject>
     */
    public function involvedFundraisingProjects(): EloquentCollection
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return new EloquentCollection;
        }

        return FundraisingProject::query()
            ->involvingAsCustomer($user)
            ->latest()
            ->limit(5)
            ->get();
    }

    public function fundraisingProjectsUrl(): string
    {
        return CustomerFundraisingProjectResource::getUrl('index');
    }

    public function fundraisingProjectUrl(FundraisingProject $project): string
    {
        return CustomerFundraisingProjectResource::getUrl('view', ['record' => $project]);
    }
}
