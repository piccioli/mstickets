<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Resources\Tickets\Support\TicketFieldAccess;
use Filament\Pages\Dashboard as BaseDashboard;

/**
 * Landing per ruolo (§6.7.2, US-113): admin/manager/developer atterrano sulla
 * vista di lavoro, non sulla dashboard di default. Sottoclasse (non middleware)
 * perché la Dashboard di Filament è già la pagina registrata sulla root del
 * pannello (`$routePath = '/'`, ereditato): sostituirla in
 * `AdminPanelProvider::pages()` con questa è l'unico punto che decide la
 * landing, mai un controllo sparso altrove. `TicketFieldAccess` (US-110) è già
 * il proxy "è staff" (concesso solo ad admin/manager/developer): riusato qui
 * invece di un secondo `hasRole()` per lo stesso concetto. Customer e
 * fundraising continuano a vedere questa Dashboard invariata (nessuna
 * dashboard cliente/elenco opportunità dedicati esiste ancora, fuori scope
 * Fase 1).
 */
class Dashboard extends BaseDashboard
{
    public function mount(): void
    {
        if (TicketFieldAccess::canManageInternalFields()) {
            $this->redirect(WorkBoard::getUrl());
        }
    }
}
