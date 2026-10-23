<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\CaiDirectory\Enums\CaiHutSort;
use App\Domain\CaiDirectory\Enums\CaiHutStatus;
use App\Domain\CaiDirectory\Enums\CaiHutType;
use App\Domain\CaiDirectory\Models\CaiHut;
use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Models\CaiSubsection;
use App\Domain\CaiDirectory\Support\CaiHutFilters;
use App\Domain\CaiDirectory\Support\CaiMapMarker;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Support\Formatting\ItalianNumberFormatter;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

/**
 * Rifugi di Sezione (Fase 10.4, US-1034): contatori, elenco filtrabile e ordinabile e mappa con cartografia CAI
 * (`x-cai.map`) dei rifugi della Sezione collegata all'utente (`cai_huts.branch_code` = `codice_cai`). Sostituisce la
 * pagina "in arrivo" della 10.1 mantenendo classe e slug (il badge "Rifugi" della Dashboard punta qui).
 *
 * Filtri e ordinamento vivono nell'URL (`?tipo[]=bivacco&stato=in-attivita&ordina=quota-desc`) e si applicano in PHP
 * con {@see CaiHutFilters}; un valore non riconosciuto vale come assente. Un utente di Sottosezione o senza Sezione
 * vede un avviso, come nella Dashboard (i rifugi sono intestati alle Sezioni).
 *
 * Fuori dalla navigazione (si raggiunge dal badge). Gate: solo un customer con `customer_type = Sezione`.
 */
class SectionHuts extends Page
{
    /** Card mostrate su mobile prima di "Mostra altri N". */
    public const MOBILE_PAGE_SIZE = 12;

    /** Nome della mappa negli eventi `cai-map-*` (vedi `x-cai.map`). */
    public const MAP_NAME = 'huts';

    protected string $view = 'filament.pages.section-huts';

    protected static ?string $title = 'Rifugi di Sezione';

    protected static ?string $slug = 'sezione/rifugi';

    protected static bool $shouldRegisterNavigation = false;

    /** @var list<string> chiavi dei tipi selezionati ({@see CaiHutType::key()}), vuoto = tutti */
    #[Url(as: 'tipo')]
    public array $types = [];

    /** Chiave dello stato ({@see CaiHutStatus::key()}), `null` = tutti. */
    #[Url(as: 'stato')]
    public ?string $status = null;

    /** Ordinamento ({@see CaiHutSort}), `null` = nome A–Z. */
    #[Url(as: 'ordina')]
    public ?string $sort = null;

    /** Mobile: tutte le card invece delle prime {@see MOBILE_PAGE_SIZE}. */
    public bool $showAll = false;

    private bool $sectionResolved = false;

    private ?CaiSection $section = null;

    /** @var Collection<int, CaiHut>|null */
    private ?Collection $huts = null;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $user->hasRole(UserRole::Customer->value)
            && $user->customer_type === CustomerType::Sezione;
    }

    /** L'intestazione è resa dalla vista, non dall'header standard di Filament. */
    public function getHeading(): string
    {
        return '';
    }

    public function toggleType(string $key): void
    {
        $type = CaiHutType::fromKey($key);

        if ($type === null) {
            return;
        }

        $selected = $this->activeTypes();
        $selected = in_array($type, $selected, true)
            ? array_values(array_filter($selected, fn (CaiHutType $current): bool => $current !== $type))
            : [...$selected, $type];

        // Ordine stabile nell'URL, indipendente dall'ordine dei clic.
        $this->types = array_values(array_map(
            fn (CaiHutType $current): string => $current->key(),
            array_filter(CaiHutType::cases(), fn (CaiHutType $case): bool => in_array($case, $selected, true)),
        ));
    }

    public function clearTypes(): void
    {
        $this->types = [];
    }

    /** Menu degli stati: chiave dello stato, stringa vuota = tutti. */
    public function filterStatus(?string $key): void
    {
        $this->status = CaiHutStatus::fromKey($key)?->key();
    }

    /** Menu "Ordina" (mobile): valore di {@see CaiHutSort}. */
    public function applySortOption(string $value): void
    {
        $this->applySort(CaiHutSort::tryFrom($value) ?? CaiHutSort::NameAsc);
    }

    public function sortByName(): void
    {
        $this->applySort($this->activeSort()->toggledByName());
    }

    public function sortByAltitude(): void
    {
        $this->applySort($this->activeSort()->toggledByAltitude());
    }

    public function resetFilters(): void
    {
        $this->types = [];
        $this->status = null;
    }

    /** @return list<CaiHutType> */
    public function activeTypes(): array
    {
        return array_values(array_filter(
            CaiHutType::cases(),
            fn (CaiHutType $type): bool => in_array($type->key(), $this->types, true),
        ));
    }

    public function activeStatus(): ?CaiHutStatus
    {
        return CaiHutStatus::fromKey($this->status);
    }

    public function activeSort(): CaiHutSort
    {
        return CaiHutSort::tryFrom((string) $this->sort) ?? CaiHutSort::NameAsc;
    }

    public function filters(): CaiHutFilters
    {
        return new CaiHutFilters($this->activeTypes(), $this->activeStatus(), $this->activeSort());
    }

    /** Sezione collegata all'utente, calcolata una sola volta per richiesta. */
    public function section(): ?CaiSection
    {
        if ($this->sectionResolved) {
            return $this->section;
        }

        $this->sectionResolved = true;
        $user = Auth::user();

        return $this->section = $user instanceof User
            ? CaiSection::query()->where('user_id', $user->id)->first()
            : null;
    }

    /** Sottosezione collegata all'utente, solo se non ha una Sezione. */
    public function subsection(): ?CaiSubsection
    {
        $user = Auth::user();

        if ($this->section() !== null || ! $user instanceof User) {
            return null;
        }

        return CaiSubsection::query()->where('user_id', $user->id)->first();
    }

    /** @return Collection<int, CaiHut> tutti i rifugi della Sezione, qualunque stato */
    public function huts(): Collection
    {
        return $this->huts ??= $this->section()?->huts()->get() ?? new Collection;
    }

    /** @return Collection<int, CaiHut> rifugi filtrati e ordinati */
    public function rows(): Collection
    {
        return $this->filters()->apply($this->huts());
    }

    /** Numero dei rifugi di un tipo, "—" se nessuno (chip del filtro). */
    public function typeCount(CaiHutType $type): string
    {
        $count = $this->huts()->filter(fn (CaiHut $hut): bool => $hut->type === $type)->count();

        return $count === 0 ? '—' : (string) $count;
    }

    /** Contatore "in attività": numero, "—" se la Sezione non ha rifugi. */
    public function activeCount(): string
    {
        $huts = $this->huts();

        return $huts->isEmpty() ? '—' : (string) $huts->filter(fn (CaiHut $hut): bool => $hut->status === CaiHutStatus::Active)->count();
    }

    /** "/ N" accanto al contatore "in attività", vuoto se la Sezione non ha rifugi. */
    public function totalCount(): string
    {
        return $this->huts()->isEmpty() ? '' : '/ '.$this->huts()->count();
    }

    /** Posti letto di tutti i rifugi della Sezione, "—" se nessuno. */
    public function bedsCount(): string
    {
        $beds = (int) $this->huts()->sum(fn (CaiHut $hut): int => $hut->beds ?? 0);

        return $beds === 0 ? '—' : ItalianNumberFormatter::integer($beds);
    }

    /** "N strutture" oppure, con un filtro che esclude qualcosa, "n di N strutture". */
    public function countLabel(int $shown): string
    {
        $total = $this->huts()->count();

        return $shown === $total
            ? $total.' '.($total === 1 ? 'struttura' : 'strutture')
            : $shown.' di '.$total.' strutture';
    }

    /** Data dell'ultimo aggiornamento dei dati di rifugi.cai.it fra i rifugi della Sezione. */
    public function lastUpdate(): ?Carbon
    {
        $latest = $this->huts()->max(fn (CaiHut $hut): ?int => $hut->source_updated_at?->getTimestamp());

        return $latest === null ? null : Carbon::createFromTimestamp($latest, config('app.timezone'));
    }

    /**
     * @param  Collection<int, CaiHut>  $rows
     * @return list<CaiMapMarker>
     */
    public function markers(Collection $rows): array
    {
        return $rows
            ->map(fn (CaiHut $hut): ?CaiMapMarker => CaiMapMarker::forHut($hut, $this->hutUrl($hut)))
            ->filter()
            ->values()
            ->all();
    }

    /** Scheda del rifugio ({@see CaiHutDetail}), aperta dal clic sulla riga e dal popup "Apri scheda →". */
    public function hutUrl(CaiHut $hut): ?string
    {
        return CaiHutDetail::getUrl(['idCai' => $hut->id_cai]);
    }

    /** Prima foto del rifugio (URL di rifugi.cai.it), `null` se non ne ha. */
    public static function photo(CaiHut $hut): ?string
    {
        $photo = ($hut->photos ?? [])[0] ?? null;

        return is_string($photo) && $photo !== '' ? $photo : null;
    }

    public static function altitude(CaiHut $hut): string
    {
        return $hut->altitude === null ? '—' : ItalianNumberFormatter::integer($hut->altitude);
    }

    public static function beds(CaiHut $hut): string
    {
        return $hut->beds === null ? '—' : ItalianNumberFormatter::integer($hut->beds);
    }

    /** "Comune (PR)", senza la provincia se manca; "—" senza comune. */
    public static function place(CaiHut $hut): string
    {
        if ($hut->municipality === null || $hut->municipality === '') {
            return '—';
        }

        return $hut->municipality.($hut->province === null || $hut->province === '' ? '' : ' ('.$hut->province.')');
    }

    public function dashboardUrl(): string
    {
        return Dashboard::getUrl();
    }

    /** Nell'URL solo ciò che differisce dal default (nome A–Z). */
    private function applySort(CaiHutSort $sort): void
    {
        $this->sort = $sort === CaiHutSort::NameAsc ? null : $sort->value;
    }
}
