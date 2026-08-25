<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Mail\Contracts\InboundMailTransport;
use App\Domain\Mail\Transports\WebklexImapTransport;
use Illuminate\Support\ServiceProvider;

/**
 * Lega App\Domain\Mail\Contracts\InboundMailTransport all'implementazione
 * IMAP (§7.4 del PRD, US-301): separato da AppServiceProvider perché
 * app/Domain/Mail è un modulo isolato dal resto del dominio (§4.3 del PRD,
 * stesso principio di ImportServiceProvider per app/Import). Un futuro
 * provider a webhook sostituirebbe solo questo binding, senza toccare la
 * pipeline che dipende dall'interfaccia.
 */
class MailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(InboundMailTransport::class, fn (): WebklexImapTransport => new WebklexImapTransport(
            config('mail_pipeline.imap', []),
            config('mail_pipeline.folders', []),
        ));
    }
}
