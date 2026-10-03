<?php

declare(strict_types=1);

namespace App\Filament\Providers;

use App\Filament\Auth\Middleware\EnsureRoleRequiresMultiFactorAuthentication;
use App\Filament\Auth\Pages\Login;
use App\Filament\Auth\Pages\RequestPasswordReset;
use App\Filament\Auth\Pages\ResetPassword;
use App\Filament\Navigation\CaiBilanciNavigationItem;
use App\Filament\Navigation\MailpitNavigationItem;
use App\Filament\Pages\CustomerDashboard;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\NotificationPreferences;
use App\Filament\Pages\WorkBoard;
use App\Support\DesignTokens;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->passwordReset(RequestPasswordReset::class, ResetPassword::class)
            ->profile()
            ->multiFactorAuthentication(
                [AppAuthentication::make()->recoverable()],
                isRequired: true,
            )
            ->multiFactorAuthenticationRequiredMiddlewareName(EnsureRoleRequiresMultiFactorAuthentication::class)
            ->brandName('Montagna Servizi')
            ->brandLogo(asset('images/branding/montagna-servizi-logo.png'))
            ->darkModeBrandLogo(asset('images/branding/montagna-servizi-logo-white.png'))
            ->brandLogoHeight('2rem')
            ->favicon(asset('images/branding/montagna-servizi-mark.png'))
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->font(DesignTokens::primaryFontFamily())
            ->colors([
                'primary' => DesignTokens::get('ms-brand'),
                'danger' => DesignTokens::get('ms-action-danger'),
                'warning' => DesignTokens::get('ms-action-warning-cta'),
                'info' => DesignTokens::get('ms-action-info-cta'),
                'success' => DesignTokens::get('ms-success-dot'),
                'gray' => DesignTokens::get('ms-text-muted'),
            ])
            ->databaseNotifications()
            ->navigationItems([
                // Genitori senza URL del gruppo "Anagrafica CAI": Filament scarta un genitore senza figli
                // visibili, quindi un sotto-menu vuoto per l'utente non compare (US-940).
                NavigationItem::make('Sezioni')
                    ->group('Anagrafica CAI')
                    ->icon(Heroicon::OutlinedBuildingLibrary)
                    ->sort(10),
                CaiBilanciNavigationItem::make(),
                NavigationItem::make('Gruppi regionali')
                    ->group('Anagrafica CAI')
                    ->icon(Heroicon::OutlinedUserGroup)
                    ->sort(30),
                NavigationItem::make('Mailpit')
                    ->group('Email')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->url(fn (): ?string => MailpitNavigationItem::url(), shouldOpenInNewTab: true)
                    ->visible(fn (): bool => MailpitNavigationItem::isVisible()),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
                WorkBoard::class,
                CustomerDashboard::class,
                NotificationPreferences::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
