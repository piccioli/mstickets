<?php

declare(strict_types=1);

namespace App\Filament\Navigation;

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Providers\AdminPanelProvider;
use Illuminate\Support\Facades\Auth;

/**
 * Regole di visibilità/URL della voce di menu "Mailpit" (§7.7 del PRD, US-324),
 * estratte in una classe pura testabile senza dover montare l'intera
 * navigazione Filament (che richiederebbe un utente autorizzato su ogni altra
 * Resource/Page del pannello). Riusata sia da
 * {@see AdminPanelProvider} sia dai test.
 *
 * Mai visibile a un customer (US-602, §8.4: "nessuna voce dei gruppi staff
 * visibile a un cliente") — è uno strumento di debug della posta interna
 * dello staff, non qualcosa di scoped al cliente.
 */
final class MailpitNavigationItem
{
    public static function isVisible(): bool
    {
        $user = Auth::user();

        if ($user instanceof User && $user->hasRole(UserRole::Customer->value)) {
            return false;
        }

        return app()->environment(['local', 'staging']) && filled(self::url());
    }

    public static function url(): ?string
    {
        $url = config('mail_pipeline.mailpit_url');

        return filled($url) ? (string) $url : null;
    }
}
