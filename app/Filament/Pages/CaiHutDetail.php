<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\CaiDirectory\Models\CaiHut;
use App\Domain\CaiDirectory\Policies\CaiHutPolicy;
use App\Domain\CaiDirectory\Support\CaiHutSheet;
use App\Domain\CaiDirectory\Support\CaiMapMarker;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Resources\CaiSections\CaiSectionResource;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

/**
 * Scheda di un rifugio (Fase 10.4, US-1035), design `docs/project/design/fase-10/rifugi-di-sezione.dc.html` §2:
 * testata, mappa, "Dove si trova", "Ospitalità e servizi", "Contatti", fonte e — solo se
 * `CaiHutPolicy::viewManagement` lo consente — "Gestione" (gestore, contratto con la scadenza, proprietà,
 * certificazioni, CIN). I dati arrivano già formattati da {@see CaiHutSheet}.
 *
 * Due livelli di autorizzazione: {@see canAccess()} ammette solo i profili che possono vedere rifugi (staff con
 * `CaiDirectoryView`, customer Sezione o GR), {@see mount()} verifica `CaiHutPolicy::view` sul singolo rifugio (Sezione
 * proprietaria, GR per prefisso, staff): un id manipolato nell'URL dà 403. Fuori dalla navigazione: si apre dall'elenco
 * dei rifugi; `?da=gr` (pagina "Rifugi del GR") cambia il link di ritorno.
 */
class CaiHutDetail extends Page
{
    /** Nome della mappa negli eventi `cai-map-*` (vedi `x-cai.map`). */
    public const MAP_NAME = 'hut';

    protected string $view = 'filament.pages.cai-hut-detail';

    protected static ?string $slug = 'rifugi';

    protected static bool $shouldRegisterNavigation = false;

    public CaiHut $hut;

    /** Pagina di provenienza: `gr` = "Rifugi del GR". */
    #[Url(as: 'da')]
    public ?string $from = null;

    public static function getRoutePath(Panel $panel): string
    {
        return parent::getRoutePath($panel).'/{idCai}';
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->can(Permission::CaiDirectoryView)) {
            return true;
        }

        return $user->hasRole(UserRole::Customer->value)
            && in_array($user->customer_type, [CustomerType::Sezione, CustomerType::GruppoRegionale], true);
    }

    public function mount(string $idCai): void
    {
        abort_unless(ctype_digit($idCai), 404);

        $hut = CaiHut::query()->findOrFail((int) $idCai);

        abort_unless(Auth::user()?->can('view', $hut) === true, 403);

        $this->hut = $hut;
    }

    public function getTitle(): string
    {
        return $this->hut->name;
    }

    /** L'intestazione è resa dalla vista (testata con la foto), non dall'header standard di Filament. */
    public function getHeading(): string
    {
        return '';
    }

    public function sheet(): CaiHutSheet
    {
        return new CaiHutSheet($this->hut);
    }

    /** Gestione riservata: Sezione proprietaria, suo GR e staff ({@see CaiHutPolicy}). */
    public function canViewManagement(): bool
    {
        return Auth::user()?->can('viewManagement', $this->hut) === true;
    }

    public function today(): Carbon
    {
        return Carbon::today(config('app.timezone'));
    }

    /** @return list<CaiMapMarker> */
    public function markers(): array
    {
        $marker = CaiMapMarker::forHut($this->hut);

        return $marker === null ? [] : [new CaiMapMarker(
            id: $marker->id,
            lat: $marker->lat,
            lng: $marker->lng,
            label: $marker->label,
            color: $marker->color,
            letter: $marker->letter,
        )];
    }

    /**
     * Elenco da cui si arriva: "Rifugi del GR" (`?da=gr` o utente GR), "Rifugi di Sezione" (utente Sezione), per lo
     * staff la scheda della Sezione proprietaria (se esiste).
     *
     * @return array{label: string, url: string}|null
     */
    public function backLink(): ?array
    {
        $user = Auth::user();
        $customerType = $user instanceof User && $user->hasRole(UserRole::Customer->value) ? $user->customer_type : null;

        if ($this->from === 'gr' || $customerType === CustomerType::GruppoRegionale) {
            return ['label' => 'Rifugi del GR', 'url' => RegionalGroupHuts::getUrl()];
        }

        if ($customerType === CustomerType::Sezione) {
            return ['label' => 'Rifugi di Sezione', 'url' => SectionHuts::getUrl()];
        }

        $section = $this->hut->section;

        return $section === null ? null : [
            'label' => $section->name,
            'url' => CaiSectionResource::getUrl('view', ['record' => $section->codice_cai]),
        ];
    }

    public function dashboardUrl(): string
    {
        return Dashboard::getUrl();
    }
}
