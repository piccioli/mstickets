<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Ticketing\Events\TicketMessagePosted;
use App\Domain\Ticketing\Events\TicketStatusChanged;
use App\Domain\Ticketing\Listeners\RestoreTicketStatusOnRequesterMessage;
use App\Domain\TimeTracking\Listeners\RecalculateWorkedTimeOnStatusChange;
use App\Support\Mail\BlockRealRecipientsOutsideProduction;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
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

        // Guard applicativo §11.8 del PRD (US-217): non un listener di dominio, ma va
        // comunque registrato qui perché Illuminate\Mail\Events\MessageSending non è
        // nella scansione di auto-discovery (nessuna classe App\Listeners\* nel repo).
        Event::listen(MessageSending::class, BlockRealRecipientsOutsideProduction::class);

        // Le viste LaTeX vivono in resources/views/latex/*.tex.blade.php (estensione doppia,
        // per distinguerle a colpo d'occhio dalle viste HTML "*.blade.php"): il resolver di
        // viste di Laravel cerca solo "blade.php"/"php"/"css"/"html" di default, quindi senza
        // questa registrazione view('latex.collaudo') non troverebbe mai il file fisico
        // "collaudo.tex.blade.php" (View::addExtension prepende l'estensione, che viene
        // comunque compilata dal motore Blade come qualunque altra vista).
        View::addExtension('tex.blade.php', 'blade');

        // La checklist di forza password nel flusso di recupero (v0.3.0) è puramente visiva
        // finché non coincide con la regola reale applicata server-side: min 8 caratteri,
        // una maiuscola, un numero — stessa soglia mostrata nell'interfaccia.
        Password::defaults(fn () => Password::min(8)->mixedCase()->numbers());
    }
}
