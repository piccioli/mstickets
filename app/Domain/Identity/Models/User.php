<?php

declare(strict_types=1);

namespace App\Domain\Identity\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Domain\Identity\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use SensitiveParameter;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'locale', 'drive_url', 'drive_budget_url'])]
#[Hidden(['password', 'remember_token', 'app_authentication_secret', 'app_authentication_recovery_codes'])]
class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'password' => 'hashed',
            'app_authentication_secret' => 'encrypted',
            'app_authentication_recovery_codes' => 'encrypted:array',
        ];
    }

    /**
     * @return BelongsToMany<Organization, $this>
     */
    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_user')->withTimestamps();
    }

    /**
     * Gate d'accesso al pannello Filament (§9.1): un utente disattivato, o senza
     * nessuno dei 5 ruoli applicativi validi, non entra nel pannello.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->deactivated_at !== null) {
            return false;
        }

        return $this->hasAnyRole(array_column(UserRole::cases(), 'value'));
    }

    /**
     * Esclude gli utenti disattivati: da usare in ogni query di selezione utenti
     * (es. campi di assegnazione ticket) perché un utente disattivato non deve
     * comparire come opzione selezionabile.
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('deactivated_at');
    }

    /**
     * The model's namespace no longer matches Laravel's default factory-name guess
     * (`Database\Factories\Domain\Identity\Models\UserFactory`): point it explicitly
     * at the real factory, which stays at `database/factories/UserFactory.php`.
     */
    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    /**
     * L'utente di sistema (§6.2.1, `config('orchestrator.system_user')`, US-022) è
     * l'unico attore ammesso per le transizioni riservate a comandi schedulati/listener
     * di dominio (es. `waiting → previous_status` per l'effetto T7, US-106/US-101).
     */
    public function isSystem(): bool
    {
        return $this->email === config('orchestrator.system_user.email');
    }

    /**
     * Risolve (creandolo se manca, come `orchestrator:doctor`) l'utente di sistema
     * (§6.2.1, US-022): fallback per l'autore di `ticket_logs`/eventi generati dal
     * sistema quando non c'è un utente autenticato, mai un id hard-coded.
     */
    public static function system(): self
    {
        return self::query()->firstOrCreate(
            ['email' => (string) config('orchestrator.system_user.email')],
            ['name' => (string) config('orchestrator.system_user.name')],
        );
    }

    /**
     * Contratti Filament per l'autenticazione da app (TOTP, US-606, §6.7.2):
     * il valore a riposo resta cifrato tramite il cast `encrypted`/`encrypted:array`
     * sopra, mai in chiaro nella colonna.
     */
    public function getAppAuthenticationSecret(): ?string
    {
        return $this->app_authentication_secret;
    }

    public function saveAppAuthenticationSecret(#[SensitiveParameter] ?string $secret): void
    {
        $this->app_authentication_secret = $secret;
        $this->save();
    }

    public function getAppAuthenticationHolderName(): string
    {
        return $this->email;
    }

    /**
     * @return ?array<string>
     */
    public function getAppAuthenticationRecoveryCodes(): ?array
    {
        return $this->app_authentication_recovery_codes;
    }

    /**
     * @param  ?array<string>  $codes
     */
    public function saveAppAuthenticationRecoveryCodes(#[SensitiveParameter] ?array $codes): void
    {
        $this->app_authentication_recovery_codes = $codes;
        $this->save();
    }
}
