<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Resources\FundraisingOpportunities\FundraisingOpportunityResource;
use App\Filament\Resources\Tickets\Support\TicketFieldAccess;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

/**
 * Landing per ruolo (§6.7.2, US-113, US-602): admin/manager/developer
 * atterrano sulla vista di lavoro, customer sulla dashboard cliente
 * (US-601), fundraising sull'elenco opportunità — mai sulla dashboard di
 * default. Sottoclasse (non middleware) perché la Dashboard di Filament è
 * già la pagina registrata sulla root del pannello (`$routePath = '/'`,
 * ereditato): sostituirla in `AdminPanelProvider::pages()` con questa è
 * l'unico punto che decide la landing, mai un controllo sparso altrove.
 * `TicketFieldAccess` (US-110) è già il proxy "è staff" (concesso solo ad
 * admin/manager/developer): riusato qui invece di un secondo `hasRole()` per
 * lo stesso concetto; per customer/fundraising serve invece l'esatto ruolo
 * (stesso idioma di {@see CustomerDashboard::canAccess()}), perché non
 * corrispondono a un permesso "ombrello" ma a un ruolo applicativo preciso.
 * Con US-602 ogni ruolo autenticato viene sempre reindirizzato altrove: mai
 * più una destinazione reale, quindi fuori da qualunque voce di navigazione
 * (altrimenti un cliente vedrebbe due voci "Dashboard", una delle quali lo
 * rimbalza subito su {@see CustomerDashboard}).
 */
class Dashboard extends BaseDashboard
{
    protected static bool $shouldRegisterNavigation = false;

    public function mount(): void
    {
        if (TicketFieldAccess::canManageInternalFields()) {
            $this->redirect(WorkBoard::getUrl());

            return;
        }

        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        if ($user->hasRole(UserRole::Customer->value)) {
            $this->redirect(CustomerDashboard::getUrl());

            return;
        }

        if ($user->hasRole(UserRole::Fundraising->value)) {
            $this->redirect(FundraisingOpportunityResource::getUrl('index'));
        }
    }
}
