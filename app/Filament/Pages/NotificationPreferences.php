<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Domain\Mail\Enums\NotificationType;
use App\Domain\Mail\Models\NotificationPreference;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use UnitEnum;

/**
 * Pagina personale "Preferenze di notifica" (§6.7.4, US-605): accessibile da
 * ogni ruolo autenticato (nessun permesso/gate a catalogo, a differenza delle
 * altre pagine del dominio Mail — qui l'unica autorizzazione è "sei loggato",
 * i dati sono sempre scoped al solo `Auth::user()`, stesso principio di
 * {@see CustomerDashboard}). Un utente vede/modifica solo le proprie righe in
 * `notification_preferences` (canale email): niente Policy/Gate, la query è
 * sempre esplicitamente filtrata su `user_id = Auth::id()`.
 *
 * I tipi mostrati sono solo quelli di {@see NotificationType} applicabili al
 * ruolo dell'utente ({@see NotificationType::appliesToUser()}) — un cliente
 * non vede mai "Assegnazione ticket" (E6), che non lo riguarda.
 *
 * Il gruppo di navigazione è dinamico ("Area cliente" solo per il ruolo
 * customer, altrimenti nessuno) — stesso pattern già stabilito per le
 * Resource condivise fra staff e cliente (Fase 6, US-602): mai una proprietà
 * statica, che sarebbe condivisa da ogni ruolo.
 */
class NotificationPreferences extends Page
{
    protected string $view = 'filament.pages.notification-preferences';

    protected static ?string $title = 'Preferenze di notifica';

    protected static ?string $navigationLabel = 'Preferenze di notifica';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    /**
     * @var array<string, bool>
     */
    public array $enabled = [];

    public static function canAccess(): bool
    {
        return Auth::user() instanceof User;
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasRole(UserRole::Customer->value) ? 'Area cliente' : null;
    }

    public function mount(): void
    {
        $user = $this->currentUser();

        $preferences = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('channel', 'email')
            ->get()
            ->keyBy('notification_type');

        foreach ($this->applicableTypes() as $type) {
            $preference = $preferences->get($type->value);
            $this->enabled[$type->value] = $preference === null || $preference->enabled;
        }
    }

    /**
     * @return array<int, NotificationType>
     */
    public function applicableTypes(): array
    {
        $user = $this->currentUser();

        return collect(NotificationType::cases())
            ->filter(fn (NotificationType $type): bool => $type->appliesToUser($user))
            ->values()
            ->all();
    }

    public function save(): void
    {
        $user = $this->currentUser();

        foreach ($this->applicableTypes() as $type) {
            NotificationPreference::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'notification_type' => $type->value,
                    'channel' => 'email',
                ],
                [
                    'enabled' => (bool) ($this->enabled[$type->value] ?? true),
                ],
            );
        }

        Notification::make()->success()->title('Preferenze di notifica aggiornate')->send();
    }

    private function currentUser(): User
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            throw new RuntimeException('NotificationPreferences richiede un utente autenticato.');
        }

        return $user;
    }
}
