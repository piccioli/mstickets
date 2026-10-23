<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\CaiDirectory\Enums\CaiTrailDifficulty;
use App\Domain\CaiDirectory\Enums\CaiTrailPracticability;
use App\Domain\CaiDirectory\Enums\CaiTrailSort;
use App\Domain\CaiDirectory\Enums\CaiTrailStatus;
use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Models\CaiSubsection;
use App\Domain\CaiDirectory\Models\CaiTrail;
use App\Domain\CaiDirectory\Support\CaiMapLine;
use App\Domain\CaiDirectory\Support\CaiSectionShortName;
use App\Domain\CaiDirectory\Support\CaiTrailFilters;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Support\Formatting\ItalianNumberFormatter;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

/**
 * Sentieri di Sezione (Fase 10.6, US-1054): riepilogo dei km per stato di accatastamento e per percorribilità, mappa
 * con le linee colorate per stato (`x-cai.map`) ed elenco filtrabile e ordinabile dei sentieri osm2cai della Sezione
 * collegata all'utente (`cai_trail_section.section_code` = `codice_cai`; un sentiero condiviso compare per intero in
 * ogni sua Sezione, con la nota "Condiviso con …"). Sostituisce la pagina "in arrivo" della 10.1 mantenendo classe e
 * slug (il badge "Sentieri" della Dashboard punta qui).
 *
 * Filtri e ordinamento vivono nell'URL (`?stato[]=4&difficolta=ee&percorribilita=non-percorribile&ordina=km-desc`) e
 * si applicano in PHP con {@see CaiTrailFilters}; un valore non riconosciuto vale come assente. La mappa riceve le sole
 * linee filtrate, come nel design. Un utente di Sottosezione o senza Sezione vede un avviso (i sentieri sono intestati
 * alle Sezioni).
 *
 * Fuori dalla navigazione (si raggiunge dal badge). Gate: solo un customer con `customer_type = Sezione`.
 */
class SectionTrails extends Page
{
    /** Card mostrate su mobile prima di "Mostra altri N". */
    public const MOBILE_PAGE_SIZE = 12;

    /** Nome della mappa negli eventi `cai-map-*` (vedi `x-cai.map`). */
    public const MAP_NAME = 'trails';

    /** Sotto questa quota della barra (in %) il segmento non porta l'etichetta dei km, come nel design. */
    private const BAR_LABEL_MIN_PERCENT = 9;

    protected string $view = 'filament.pages.section-trails';

    protected static ?string $title = 'Sentieri di Sezione';

    protected static ?string $slug = 'sezione/sentieri';

    protected static bool $shouldRegisterNavigation = false;

    /** @var list<string> stati selezionati (valori di {@see CaiTrailStatus}), vuoto = tutti */
    #[Url(as: 'stato')]
    public array $statuses = [];

    /** Difficoltà ({@see CaiTrailDifficulty}), `null` = tutte. */
    #[Url(as: 'difficolta')]
    public ?string $difficulty = null;

    /** Percorribilità ({@see CaiTrailPracticability::key()}), `null` = tutte. */
    #[Url(as: 'percorribilita')]
    public ?string $practicability = null;

    /** Ordinamento ({@see CaiTrailSort}), `null` = numero crescente. */
    #[Url(as: 'ordina')]
    public ?string $sort = null;

    /** Mobile: tutte le card invece delle prime {@see MOBILE_PAGE_SIZE}. */
    public bool $showAll = false;

    private bool $sectionResolved = false;

    private ?CaiSection $section = null;

    /** @var Collection<int, CaiTrail>|null */
    private ?Collection $trails = null;

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

    public function toggleStatus(int $value): void
    {
        $status = CaiTrailStatus::tryFrom($value);

        if ($status === null) {
            return;
        }

        $selected = $this->activeStatuses();
        $selected = in_array($status, $selected, true)
            ? array_values(array_filter($selected, fn (CaiTrailStatus $current): bool => $current !== $status))
            : [...$selected, $status];

        // Ordine stabile nell'URL, indipendente dall'ordine dei clic.
        $this->statuses = array_values(array_map(
            fn (CaiTrailStatus $current): string => (string) $current->value,
            array_filter(CaiTrailStatus::cases(), fn (CaiTrailStatus $case): bool => in_array($case, $selected, true)),
        ));
    }

    public function clearStatuses(): void
    {
        $this->statuses = [];
    }

    /** Menu delle difficoltà: valore di {@see CaiTrailDifficulty}, stringa vuota = tutte. */
    public function filterDifficulty(?string $value): void
    {
        $this->difficulty = CaiTrailDifficulty::tryFrom((string) $value)?->value;
    }

    /** Menu delle percorribilità: chiave di {@see CaiTrailPracticability}, stringa vuota = tutte. */
    public function filterPracticability(?string $key): void
    {
        $this->practicability = CaiTrailPracticability::fromKey($key)?->key();
    }

    /** Menu "Ordina" (mobile): valore di {@see CaiTrailSort}. */
    public function applySortOption(string $value): void
    {
        $this->applySort(CaiTrailSort::tryFrom($value) ?? CaiTrailSort::NumberAsc);
    }

    /** Intestazioni della tabella: `numero`, `km`, `stato`. */
    public function sortBy(string $column): void
    {
        $this->applySort($this->activeSort()->toggled($column));
    }

    public function resetFilters(): void
    {
        $this->statuses = [];
        $this->difficulty = null;
        $this->practicability = null;
    }

    /** @return list<CaiTrailStatus> */
    public function activeStatuses(): array
    {
        $values = array_map('strval', $this->statuses);

        return array_values(array_filter(
            CaiTrailStatus::cases(),
            fn (CaiTrailStatus $status): bool => in_array((string) $status->value, $values, true),
        ));
    }

    public function activeDifficulty(): ?CaiTrailDifficulty
    {
        return CaiTrailDifficulty::tryFrom((string) $this->difficulty);
    }

    public function activePracticability(): ?CaiTrailPracticability
    {
        return CaiTrailPracticability::fromKey($this->practicability);
    }

    public function activeSort(): CaiTrailSort
    {
        return CaiTrailSort::tryFrom((string) $this->sort) ?? CaiTrailSort::NumberAsc;
    }

    public function filters(): CaiTrailFilters
    {
        return new CaiTrailFilters($this->activeStatuses(), $this->activeDifficulty(), $this->activePracticability(), $this->activeSort());
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

    /** @return Collection<int, CaiTrail> tutti i sentieri della Sezione, con le Sezioni che li gestiscono */
    public function trails(): Collection
    {
        return $this->trails ??= $this->section()?->trails()->with('sections:codice_cai,name')->get() ?? new Collection;
    }

    /** @return Collection<int, CaiTrail> sentieri filtrati e ordinati */
    public function rows(): Collection
    {
        return $this->filters()->apply($this->trails());
    }

    /** Km totali dei sentieri della Sezione, "—" se nessuno. */
    public function totalKmLabel(): string
    {
        return $this->trails()->isEmpty() ? '—' : self::km(self::sumKm($this->trails()));
    }

    /** Numero dei sentieri della Sezione, "—" se nessuno. */
    public function trailsCountLabel(): string
    {
        return $this->trails()->isEmpty() ? '—' : ItalianNumberFormatter::integer($this->trails()->count());
    }

    /**
     * Barra e legenda dei km per stato di accatastamento (1 → 4).
     *
     * @return list<array{status: CaiTrailStatus, km: string, percent: float, label: string, share: string, count: string}>
     */
    public function statusSummary(): array
    {
        $total = self::sumKm($this->trails());

        return array_map(function (CaiTrailStatus $status) use ($total): array {
            $trails = $this->trails()->filter(fn (CaiTrail $trail): bool => $trail->status === $status);
            $km = self::sumKm($trails);
            $percent = $total > 0 ? $km / $total * 100 : 0.0;

            return [
                'status' => $status,
                'km' => self::km($km),
                'percent' => round($percent, 2),
                'label' => $percent > self::BAR_LABEL_MIN_PERCENT ? self::km($km).' km' : '',
                'share' => $total > 0 ? round($percent).'%' : '—',
                'count' => self::trailsNoun($trails->count()),
            ];
        }, CaiTrailStatus::cases());
    }

    /**
     * Km e numero di sentieri per percorribilità.
     *
     * @return list<array{practicability: CaiTrailPracticability, km: string, count: string}>
     */
    public function practicabilitySummary(): array
    {
        return array_map(function (CaiTrailPracticability $practicability): array {
            $trails = $this->trails()->filter(fn (CaiTrail $trail): bool => $trail->practicability === $practicability);

            return [
                'practicability' => $practicability,
                'km' => self::km(self::sumKm($trails)),
                'count' => self::trailsNoun($trails->count()),
            ];
        }, CaiTrailPracticability::cases());
    }

    /** Numero dei sentieri di uno stato, "—" se nessuno (chip del filtro). */
    public function statusCount(CaiTrailStatus $status): string
    {
        $count = $this->trails()->filter(fn (CaiTrail $trail): bool => $trail->status === $status)->count();

        return $count === 0 ? '—' : (string) $count;
    }

    /**
     * "N sentieri · X km" oppure, con un filtro che esclude qualcosa, "n di N sentieri · x km" (km dei soli filtrati).
     *
     * @param  Collection<int, CaiTrail>  $rows
     */
    public function countLabel(Collection $rows): string
    {
        $total = $this->trails()->count();

        return $rows->count() === $total
            ? self::trailsNoun($total).' · '.self::km(self::sumKm($rows)).' km'
            : $rows->count().' di '.$total.' sentieri · '.self::km(self::sumKm($rows)).' km';
    }

    /** Nomi brevi delle altre Sezioni del sentiero ("CAI Parma, SAT"), vuoto se la Sezione è l'unica. */
    public function sharedWith(CaiTrail $trail): string
    {
        $code = $this->section()?->codice_cai;

        return $trail->sections
            ->reject(fn (CaiSection $section): bool => $section->codice_cai === $code)
            ->map(fn (CaiSection $section): string => CaiSectionShortName::of($section->name))
            ->sort()
            ->implode(', ');
    }

    /**
     * @param  Collection<int, CaiTrail>  $rows
     * @return list<CaiMapLine>
     */
    public function lines(Collection $rows): array
    {
        return $rows
            ->map(fn (CaiTrail $trail): ?CaiMapLine => CaiMapLine::forTrail($trail, $this->trailUrl($trail)))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Scheda del sentiero: arriva con la US-1055 (`CaiTrailDetail`). Finché è `null` la riga e la card selezionano la
     * linea sulla mappa, senza "›" né "Apri scheda →".
     */
    public function trailUrl(CaiTrail $trail): ?string
    {
        return null;
    }

    /** "Partenza → Arrivo", "—" al posto del dato mancante. */
    public static function route(CaiTrail $trail): string
    {
        return (filled($trail->from_place) ? $trail->from_place : '—').' → '.(filled($trail->to_place) ? $trail->to_place : '—');
    }

    /** Km del sentiero con un decimale, "—" se manca. */
    public static function trailKm(CaiTrail $trail): string
    {
        return $trail->length_km === null ? '—' : self::km((float) $trail->length_km);
    }

    /** Data dell'ultimo aggiornamento della percorribilità (gg/mm/aaaa), `null` se manca. */
    public static function practicabilityDate(CaiTrail $trail): ?string
    {
        return $trail->issues_last_update?->format('d/m/Y');
    }

    public function dashboardUrl(): string
    {
        return Dashboard::getUrl();
    }

    /** Nell'URL solo ciò che differisce dal default (numero crescente). */
    private function applySort(CaiTrailSort $sort): void
    {
        $this->sort = $sort === CaiTrailSort::NumberAsc ? null : $sort->value;
    }

    /** @param  Collection<int, CaiTrail>  $trails */
    private static function sumKm(Collection $trails): float
    {
        return (float) $trails->sum(fn (CaiTrail $trail): float => (float) $trail->length_km);
    }

    private static function km(float $km): string
    {
        return ItalianNumberFormatter::decimal($km, 1);
    }

    private static function trailsNoun(int $count): string
    {
        return $count.' '.($count === 1 ? 'sentiero' : 'sentieri');
    }
}
