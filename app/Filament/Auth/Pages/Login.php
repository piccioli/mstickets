<?php

declare(strict_types=1);

namespace App\Filament\Auth\Pages;

use Filament\Auth\Pages\Login as BaseLogin;

/**
 * Sovrascrive SOLO la view/il layout della pagina di login nativa Filament (v0.3.0,
 * design "Login Montagna Servizi" — verde pino/Manrope, distinto dal tema teal del
 * pannello). Logica di autenticazione, rate limiting, eventi, redirect: tutti nativi
 * Filament, ereditati senza modifiche — vedi vendor/filament/filament/src/Auth/Pages/Login.php.
 */
class Login extends BaseLogin
{
    protected string $view = 'filament.auth.login';

    protected static string $layout = 'filament.auth.layout';
}
