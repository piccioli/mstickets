<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Ticketing\Events\TicketMessagePosted;
use App\Domain\Ticketing\Events\TicketStatusChanged;
use App\Domain\Ticketing\Listeners\RestoreTicketStatusOnRequesterMessage;
use App\Domain\TimeTracking\Listeners\RecalculateWorkedTimeOnStatusChange;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * I listener di dominio vivono in `App\Domain\*\Listeners` (non `App\Listeners`),
     * fuori dalla scansione di auto-discovery di Laravel: vanno registrati qui a mano.
     */
    public function boot(): void
    {
        Event::listen(TicketMessagePosted::class, RestoreTicketStatusOnRequesterMessage::class);
        Event::listen(TicketStatusChanged::class, RecalculateWorkedTimeOnStatusChange::class);

        // La checklist di forza password nel flusso di recupero (v0.3.0) è puramente visiva
        // finché non coincide con la regola reale applicata server-side: min 8 caratteri,
        // una maiuscola, un numero — stessa soglia mostrata nell'interfaccia.
        Password::defaults(fn () => Password::min(8)->mixedCase()->numbers());
    }
}
