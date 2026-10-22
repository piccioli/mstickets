<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

/**
 * Pagina di approfondimento di un badge di una Dashboard V2 cliente (Sezione US-1003, Gruppo Regionale US-1013):
 * per ora mostra solo "Questa sezione è in arrivo." e il link di ritorno, così nessun badge porta a un link rotto.
 * Base astratta con una sola vista condivisa: ogni famiglia di pagine dichiara il tipo cliente ammesso
 * ({@see allowedCustomerType()}), ogni pagina solo titolo e slug. Il contenuto reale arriva con le rispettive
 * feature della Fase 10, che sostituiranno la sottoclasse (non questa base).
 *
 * Fuori dalla navigazione: si raggiunge solo dai badge. Gate: solo un customer del tipo ammesso.
 */
abstract class CustomerComingSoonPage extends Page
{
    protected string $view = 'filament.pages.coming-soon';

    protected static bool $shouldRegisterNavigation = false;

    abstract protected static function allowedCustomerType(): CustomerType;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $user->hasRole(UserRole::Customer->value)
            && $user->customer_type === static::allowedCustomerType();
    }

    /**
     * La root del pannello ({@see Dashboard::mount()}) decide già l'atterraggio per ruolo: il link di ritorno
     * resta corretto anche quando la Dashboard V2 del cliente diventa la pagina di atterraggio.
     */
    public function dashboardUrl(): string
    {
        return Dashboard::getUrl();
    }
}
