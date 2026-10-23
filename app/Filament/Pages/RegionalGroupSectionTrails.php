<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Models\CaiSubsection;
use App\Domain\CaiDirectory\Models\CaiTrail;
use App\Domain\CaiDirectory\Queries\CaiRegionalGroupDashboardMetrics;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use Filament\Panel;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;

/**
 * Sentieri di una Sezione visti dal GR (Fase 10.7, US-1065): la pagina "Sentieri di Sezione" della 10.6 (stessa vista,
 * stessi filtri e riepilogo) per la Sezione `{codice}` invece che per quella collegata all'utente, aperta dalla tabella
 * "Per Sezione" di {@see RegionalGroupTrails}. Il link di ritorno porta a "Sentieri del GR"; la scheda di un sentiero si
 * apre con `?da=sezione-gr&codice=…`, così il suo ritorno riporta qui con i filtri.
 *
 * Gate: customer GR ({@see canAccess()}) e Sezione esistente con il prefisso del GR della regione dell'utente
 * ({@see mount()}: 404 se non esiste, 403 se di un altro GR o senza regione).
 */
class RegionalGroupSectionTrails extends SectionTrails
{
    protected static ?string $slug = 'gr/sentieri/sezioni';

    /** Codice CAI della Sezione, fissato al mount (non modificabile dal client). */
    #[Locked]
    public string $sectionCode = '';

    private bool $sectionResolvedForGroup = false;

    private ?CaiSection $groupSection = null;

    public static function getRoutePath(Panel $panel): string
    {
        return parent::getRoutePath($panel).'/{codice}';
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $user->hasRole(UserRole::Customer->value)
            && $user->customer_type === CustomerType::GruppoRegionale;
    }

    public function mount(string $codice): void
    {
        abort_unless(ctype_digit($codice) && strlen($codice) <= 20, 404);

        $section = CaiSection::query()->find($codice);

        abort_if($section === null, 404);

        $user = Auth::user();
        $region = $user instanceof User ? $user->region : null;
        $prefix = $region === null ? null : CaiRegionalGroupDashboardMetrics::prefixOf($region);

        abort_unless($prefix !== null && str_starts_with((string) $section->codice_cai, $prefix), 403);

        $this->sectionCode = (string) $section->codice_cai;
    }

    public function section(): ?CaiSection
    {
        if (! $this->sectionResolvedForGroup) {
            $this->sectionResolvedForGroup = true;
            $this->groupSection = CaiSection::query()->find($this->sectionCode);
        }

        return $this->groupSection;
    }

    public function subsection(): ?CaiSubsection
    {
        return null;
    }

    /** Scheda del sentiero aperta da qui: `?da=sezione-gr&codice=…` più i filtri dell'elenco. */
    public function trailUrl(CaiTrail $trail): string
    {
        return CaiTrailDetail::getUrl(['trailId' => $trail->id, 'da' => CaiTrailDetail::FROM_GROUP_SECTION, 'codice' => $this->sectionCode, ...$this->listQuery()]);
    }

    /** @return array{label: string, url: string} */
    public function backLink(): array
    {
        return ['label' => 'Sentieri del GR', 'url' => RegionalGroupTrails::getUrl()];
    }
}
