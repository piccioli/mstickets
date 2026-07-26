<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Ticketing\Events\TicketMessagePosted;
use App\Domain\Ticketing\Listeners\RestoreTicketStatusOnRequesterMessage;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

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
    }
}
