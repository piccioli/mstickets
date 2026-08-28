<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Models\CaiSubsection;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Resources\CaiSections\Schemas\CaiSectionInfolist;
use App\Filament\Resources\Tickets\TicketResource;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

/**
 * Dettaglio completo di una Sezione CAI/RUNTS aperto da un cliente Gruppo Regionale (US-807)
 * dalla card "Sezioni del gruppo regionale" ({@see CustomerDashboard::regionalGroupSections()},
 * Fase 7 US-705). Nessuna voce di navigazione (si apre solo dal link della card): stesso
 * contenuto e stesso componente {@see CaiSectionInfolist} già usato dallo staff (US-804) e dalla
 * dashboard cliente Sezione (US-806) — vedi la partial condivisa
 * `resources/views/filament/pages/partials/cai-directory-detail.blade.php` (design doc Fase 8 §7:
 * "un solo componente/vista riusato da tutti e tre i punti di accesso").
 *
 * {@see self::canAccess()} verifica solo il tipo cliente generico (è un Gruppo Regionale): lo
 * scope sulla singola sezione (deve appartenere alla propria regione) è verificato in
 * {@see self::mount()}, perché richiede il parametro di rotta `$record` non disponibile in un
 * metodo statico — un accesso diretto via URL a una sezione di un'altra regione fallisce con 403,
 * non solo l'assenza del link in UI.
 */
class CaiSectionRegionalDetail extends Page
{
    protected string $view = 'filament.pages.cai-section-regional-detail';

    protected static bool $shouldRegisterNavigation = false;

    public User $section;

    public static function getRoutePath(Panel $panel): string
    {
        return parent::getRoutePath($panel).'/{record}';
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $user->hasRole(UserRole::Customer->value)
            && $user->customer_type === CustomerType::GruppoRegionale;
    }

    public function mount(string $record): void
    {
        $section = User::query()->where('customer_type', CustomerType::Sezione)->findOrFail($record);

        $currentUser = Auth::user();

        abort_unless(
            $currentUser instanceof User && $currentUser->region !== null && $section->region === $currentUser->region,
            403,
        );

        $this->section = $section;
    }

    public function getTitle(): string
    {
        return $this->section->name;
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewTickets')
                ->label('Vedi i ticket di questa sezione')
                ->icon(Heroicon::OutlinedTicket)
                ->url(TicketResource::getUrl('index', [
                    'tableFilters' => ['requester_id' => ['value' => $this->section->id]],
                ])),
        ];
    }

    /**
     * Sezione CAI collegata alla Sezione mostrata (stesso idioma di
     * {@see CustomerDashboard::caiSection()}, ma per `$this->section` invece dell'utente
     * autenticato).
     */
    public function caiSection(): ?CaiSection
    {
        return CaiSection::query()->where('user_id', $this->section->id)->first();
    }

    /**
     * @see CustomerDashboard::caiSubsection()
     */
    public function caiSubsection(): ?CaiSubsection
    {
        return CaiSubsection::query()->where('user_id', $this->section->id)->first();
    }

    /**
     * @see CustomerDashboard::caiSectionInfolist()
     */
    public function caiSectionInfolist(Schema $schema): Schema
    {
        return CaiSectionInfolist::configure($schema)->record($this->caiSection());
    }
}
