<?php

declare(strict_types=1);

namespace App\Filament\Navigation;

use App\Filament\Providers\AdminPanelProvider;

/**
 * Regole di visibilità/URL della voce di menu "Mailpit" (§7.7 del PRD, US-324),
 * estratte in una classe pura testabile senza dover montare l'intera
 * navigazione Filament (che richiederebbe un utente autorizzato su ogni altra
 * Resource/Page del pannello). Riusata sia da
 * {@see AdminPanelProvider} sia dai test.
 */
final class MailpitNavigationItem
{
    public static function isVisible(): bool
    {
        return app()->environment(['local', 'staging']) && filled(self::url());
    }

    public static function url(): ?string
    {
        $url = config('mail_pipeline.mailpit_url');

        return filled($url) ? (string) $url : null;
    }
}
