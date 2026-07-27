<?php

declare(strict_types=1);

namespace App\Filament\Auth\Pages;

use Filament\Auth\Pages\PasswordReset\ResetPassword as BaseResetPassword;

/**
 * Sovrascrive SOLO la view (design "Login Montagna Servizi", v0.3.0). Logica di reset
 * (broker, hashing, rate limiting, evento `PasswordReset`): nativa Filament, invariata —
 * vedi vendor/filament/filament/src/Auth/Pages/PasswordReset/ResetPassword.php.
 */
class ResetPassword extends BaseResetPassword
{
    protected string $view = 'filament.auth.reset-password';

    protected static string $layout = 'filament.auth.layout';
}
