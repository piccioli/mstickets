<?php

declare(strict_types=1);

namespace App\Filament\Auth\Middleware;

use App\Domain\Identity\Models\User;
use Closure;
use Filament\Auth\MultiFactor\Http\Middleware\EnsureMultiFactorAuthenticationIsEnabled;
use Filament\Facades\Filament;
use Illuminate\Http\Request;

/**
 * Applica l'obbligo di MFA (US-606, §6.7.2) solo ai ruoli elencati in
 * config('mfa.required_roles'): Filament espone un solo booleano "MFA richiesta"
 * per l'intero pannello (Panel::isMultiFactorAuthenticationRequired()), valutato
 * alla registrazione delle route — prima che la sessione della richiesta corrente
 * sia disponibile, quindi non utilizzabile per un controllo per-ruolo in quel punto.
 * Questo middleware sostituisce quello nativo
 * (Filament\Auth\MultiFactor\Http\Middleware\EnsureMultiFactorAuthenticationIsEnabled,
 * vedi AdminPanelProvider::panel()) e gli delega l'enforcement solo per gli utenti
 * il cui ruolo lo richiede, lasciando passare tutti gli altri.
 */
class EnsureRoleRequiresMultiFactorAuthentication
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User || ! $user->hasAnyRole(config('mfa.required_roles', []))) {
            return $next($request);
        }

        return app(EnsureMultiFactorAuthenticationIsEnabled::class)->handle($request, $next);
    }
}
