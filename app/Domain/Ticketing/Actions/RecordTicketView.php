<?php

declare(strict_types=1);

namespace App\Domain\Ticketing\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketView;
use Illuminate\Support\Facades\DB;

/**
 * Unico punto di ingresso per registrare/aggiornare un `ticket_view` (§6.2.3 del
 * PRD, US-108): pensato per essere invocato da un hook ESPLICITO sulla pagina di
 * dettaglio del ticket (es. `ViewTicket::mount()` quando US-110 costruirà la
 * risorsa Filament), mai da un middleware con pattern matching sulle URL. Al
 * massimo una riga per (ticket, utente, giorno): `last_viewed_at`/`view_count`
 * avanzano solo se è trascorsa la soglia di throttling configurata, non ad ogni
 * chiamata. Nessuna scrittura in `ticket_logs`: le visualizzazioni restano una
 * tabella separata dai log di dominio (§6.2.1).
 */
final class RecordTicketView
{
    public static function run(Ticket $ticket, User $user): TicketView
    {
        $viewedOn = today()->toDateString();

        return DB::transaction(function () use ($ticket, $user, $viewedOn): TicketView {
            $ticketView = TicketView::query()
                ->where('ticket_id', $ticket->id)
                ->where('user_id', $user->id)
                ->whereDate('viewed_on', $viewedOn)
                ->lockForUpdate()
                ->first();

            if ($ticketView === null) {
                return TicketView::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $user->id,
                    'viewed_on' => $viewedOn,
                    'last_viewed_at' => now(),
                    'view_count' => 1,
                ]);
            }

            if ($ticketView->last_viewed_at->diffInMinutes(now()) >= self::throttleMinutes()) {
                $ticketView->update([
                    'last_viewed_at' => now(),
                    'view_count' => $ticketView->view_count + 1,
                ]);
            }

            return $ticketView;
        });
    }

    private static function throttleMinutes(): int
    {
        return (int) config('ticketing.views.throttle_minutes');
    }
}
