<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\CaiDirectory\Enums\CaiTrailPracticability;
use App\Domain\CaiDirectory\Enums\CaiTrailSort;
use App\Domain\CaiDirectory\Enums\CaiTrailStatus;
use App\Domain\CaiDirectory\Models\CaiTrail;
use App\Domain\CaiDirectory\Queries\CaiRegionalGroupTrails;
use App\Domain\CaiDirectory\Support\CaiMapArea;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupTrailFilters;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupTrailProvinceRow;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupTrailRow;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupTrailsData;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupTrailSectionRow;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupTrailSectorRow;
use App\Domain\CaiDirectory\Support\CaiSectionShortName;
use App\Domain\CaiDirectory\Support\CaiTrailKmStats;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Widgets\RegionalGroupTrailsMap;
use App\Support\Formatting\ItalianNumberFormatter;
use Filament\Pages\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

/**
 * Sentieri del GR (Fase 10.7, US-1065), design `docs/project/design/fase-10/sentieri-del-gr.dc.html`: riepilogo
 * regionale con i **due totali** (km nel territorio regionale, km gestiti dalle Sezioni del GR), sentieri presenti, barra
 * per stato con i consolidati (3–4), percorribilità; mappa per settori (widget {@see RegionalGroupTrailsMap},
 * componente figlio: i settori non viaggiano a ogni filtro); "Per provincia" con i settori della provincia aperta; "Per
 * Sezione" con la nota sulla somma che supera i km gestiti; elenco paginato in SQL dei sentieri presenti (≥ 50% nella
 * regione) con ricerca e filtri. Sostituisce la pagina "in arrivo" della 10.2 mantenendo classe e slug (il badge
 * "Sentieri" della Dashboard GR punta qui).
 *
 * Dati da {@see CaiRegionalGroupTrails} per la `region` dell'utente. Ricerca, filtri, ordinamento, pagina e provincia
 * aperta vivono nell'URL (`?cerca=…&provincia=20&settore=301&sezione=9300001&stato=4&percorribilita=…&ordina=km-desc
 * &pagina=2&aperta=20`, solo ciò che è impostato); un valore non riconosciuto vale come assente. La scheda del sentiero
 * si apre con `?da=gr` più i parametri dell'elenco ({@see LIST_QUERY_KEYS}), che il suo link di ritorno riporta; una
 * Sezione apre {@see RegionalGroupSectionTrails}. Ricerca, filtro e ordinamento della tabella "Per Sezione" restano
 * locali (non nell'URL).
 *
 * Fuori dalla navigazione (si raggiunge dal badge). Gate: solo un customer con `customer_type = GruppoRegionale`.
 */
class RegionalGroupTrails extends Page
{
    /** Righe di una pagina dell'elenco (desktop e mobile). */
    public const PER_PAGE = 25;

    /** Sezioni mostrate su mobile prima di "Mostra tutte le N Sezioni". */
    public const MOBILE_SECTIONS = 10;

    /** Nome della mappa negli eventi `cai-map-*` (vedi `x-cai.map`). */
    public const MAP_NAME = 'gr-trails';

    /** Parametri dell'URL dell'elenco, riportati dalla scheda del sentiero nel link di ritorno. */
    public const LIST_QUERY_KEYS = ['cerca', 'provincia', 'settore', 'sezione', 'stato', 'percorribilita', 'ordina', 'pagina'];

    /** Filtro della tabella "Per Sezione": tutte, con sentieri, senza sentieri. */
    public const SECTIONS_ALL = 'tutte';

    public const SECTIONS_WITH_TRAILS = 'con';

    public const SECTIONS_WITHOUT_TRAILS = 'senza';

    /** Sotto questa quota della barra (in %) il segmento non porta l'etichetta dei km, come nel design. */
    private const BAR_LABEL_MIN_PERCENT = 9;

    protected string $view = 'filament.pages.regional-group-trails';

    protected static ?string $title = 'Sentieri del GR';

    protected static ?string $slug = 'gr/sentieri';

    protected static bool $shouldRegisterNavigation = false;

    /** Ricerca per numero, nome, partenza o arrivo. */
    #[Url(as: 'cerca', except: '')]
    public string $search = '';

    /** Id della provincia osm2cai, `null` = tutte. */
    #[Url(as: 'provincia')]
    public ?string $province = null;

    /** Id del settore osm2cai, `null` = tutti. */
    #[Url(as: 'settore')]
    public ?string $sector = null;

    /** Codice CAI della Sezione o {@see CaiRegionalGroupTrailFilters::WITHOUT_SECTION}, `null` = tutte. */
    #[Url(as: 'sezione')]
    public ?string $section = null;

    /** Valore di {@see CaiTrailStatus} (1–4), `null` = tutti. */
    #[Url(as: 'stato')]
    public ?string $status = null;

    /** Chiave di {@see CaiTrailPracticability}, `null` = tutte. */
    #[Url(as: 'percorribilita')]
    public ?string $practicability = null;

    /** Ordinamento ({@see CaiTrailSort}), `null` = numero crescente. */
    #[Url(as: 'ordina')]
    public ?string $sort = null;

    /** Pagina dell'elenco (da 1), `null` = prima. */
    #[Url(as: 'pagina')]
    public ?string $listPage = null;

    /** Id della provincia aperta nella tabella "Per provincia", `null` = nessuna. */
    #[Url(as: 'aperta')]
    public ?string $openProvince = null;

    /** Ricerca della tabella "Per Sezione". */
    public string $sectionSearch = '';

    /** Filtro della tabella "Per Sezione": {@see SECTIONS_ALL}, {@see SECTIONS_WITH_TRAILS}, {@see SECTIONS_WITHOUT_TRAILS}. */
    public string $sectionFilter = self::SECTIONS_ALL;

    /** Colonna di ordinamento della tabella "Per Sezione": `nome`, `sentieri`, `km`. */
    public string $sectionSort = 'km';

    public bool $sectionSortDescending = true;

    /** Mobile: tutte le Sezioni invece delle prime {@see MOBILE_SECTIONS}. */
    public bool $showAllSections = false;

    private bool $dataResolved = false;

    private ?CaiRegionalGroupTrailsData $data = null;

    /** @var LengthAwarePaginator<int, CaiRegionalGroupTrailRow>|null */
    private ?LengthAwarePaginator $trails = null;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && $user->hasRole(UserRole::Customer->value)
            && $user->customer_type === CustomerType::GruppoRegionale;
    }

    /** L'intestazione è resa dalla vista, non dall'header standard di Filament. */
    public function getHeading(): string
    {
        return '';
    }

    public function updatedSearch(): void
    {
        $this->listPage = null;
    }

    /** Provincia dell'elenco: il settore resta solo se è di quella provincia. */
    public function filterProvince(?string $id): void
    {
        $province = $this->findProvince($id);
        $this->province = $province === null ? null : (string) $province->id;

        if ($province !== null && ctype_digit((string) $this->sector) && $this->provinceOfSector((int) $this->sector)?->id !== $province->id) {
            $this->sector = null;
        }

        $this->listPage = null;
    }

    /** Settore dell'elenco: imposta anche la sua provincia, come nel design. */
    public function filterSector(?string $id): void
    {
        $province = $this->provinceOfSector((int) $id);
        $this->sector = $province === null ? null : (string) (int) $id;
        $this->province = $province === null ? $this->province : (string) $province->id;
        $this->listPage = null;
    }

    /**
     * "Elenco sentieri →" di un settore (tabella "Per provincia" o riquadro della mappa, `cai-map-area-listed` con id
     * `settore-{id}`): elenco filtrato per provincia e settore.
     */
    public function listSector(string|int $id): void
    {
        $this->filterSector((string) preg_replace('/^settore-/', '', (string) $id));
    }

    public function filterSection(?string $key): void
    {
        $this->section = $this->validSection($key);
        $this->listPage = null;
    }

    public function filterStatus(?string $value): void
    {
        $this->status = CaiTrailStatus::tryFrom((int) $value) === null ? null : (string) (int) $value;
        $this->listPage = null;
    }

    public function filterPracticability(?string $key): void
    {
        $this->practicability = CaiTrailPracticability::fromKey($key)?->key();
        $this->listPage = null;
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->province = null;
        $this->sector = null;
        $this->section = null;
        $this->status = null;
        $this->practicability = null;
        $this->listPage = null;
    }

    /** Intestazioni dell'elenco: `numero`, `km`, `stato` (primo clic su km e stato: decrescente). */
    public function sortBy(string $column): void
    {
        $sort = $this->activeSort()->toggled($column);
        $this->sort = $sort === CaiTrailSort::NumberAsc ? null : $sort->value;
        $this->listPage = null;
    }

    public function goToPage(int $page): void
    {
        $page = max(1, min($page, $this->trails()->lastPage()));
        $this->listPage = $page === 1 ? null : (string) $page;
        // La pagina calcolata qui sopra è quella vecchia: la vista deve rileggerla.
        $this->trails = null;
    }

    public function toggleProvince(int $id): void
    {
        $this->openProvince = $this->openProvince === (string) $id || $this->findProvince((string) $id) === null ? null : (string) $id;
    }

    /** Intestazioni della tabella "Per Sezione": `nome` (primo clic crescente), `sentieri`, `km` (decrescenti). */
    public function sortSections(string $column): void
    {
        if (! in_array($column, ['nome', 'sentieri', 'km'], true)) {
            return;
        }

        $this->sectionSortDescending = $this->sectionSort === $column ? ! $this->sectionSortDescending : $column !== 'nome';
        $this->sectionSort = $column;
    }

    public function filterSections(string $filter): void
    {
        $this->sectionFilter = in_array($filter, [self::SECTIONS_WITH_TRAILS, self::SECTIONS_WITHOUT_TRAILS], true) ? $filter : self::SECTIONS_ALL;
        $this->showAllSections = false;
    }

    public function region(): ?Region
    {
        $user = Auth::user();

        return $user instanceof User ? $user->region : null;
    }

    /** Numeri della pagina per la regione dell'utente, `null` se l'utente non ha regione. */
    public function data(): ?CaiRegionalGroupTrailsData
    {
        if ($this->dataResolved) {
            return $this->data;
        }

        $this->dataResolved = true;
        $region = $this->region();

        return $this->data = $region === null ? null : CaiRegionalGroupTrails::forRegion($region);
    }

    /** "GR Lombardia · Codice CAI 9300000", senza il codice se la regione non ha Sezioni. */
    public function subtitle(CaiRegionalGroupTrailsData $data): string
    {
        return $data->codiceCai === null ? $data->groupName : $data->groupName.' · Codice CAI '.$data->codiceCai;
    }

    // ---- Riepilogo ----

    /**
     * Le tre card del riepilogo: i due totali di km e i sentieri presenti.
     *
     * @return array{territoryKm: string, managedKm: string, managedWidth: float, managedShare: string, withoutSectionKm: string, present: string, withoutSection: string, shared: string, provinces: int, sectors: int}
     */
    public function summary(CaiRegionalGroupTrailsData $data): array
    {
        $territory = $data->territory->km();
        $share = $territory > 0 ? $data->managedKm / $territory : null;

        return [
            'territoryKm' => self::kmRounded($territory),
            'managedKm' => self::kmRounded($data->managedKm),
            'managedWidth' => $share === null ? 0.0 : round(min(1.0, $share) * 100, 1),
            'managedShare' => self::percent($share),
            'withoutSectionKm' => self::kmRounded($data->presentWithoutSectionKm),
            'present' => ItalianNumberFormatter::integer($data->territory->trailsCount()),
            'withoutSection' => ItalianNumberFormatter::integer($data->presentWithoutSectionCount),
            'shared' => ItalianNumberFormatter::integer($data->presentSharedCount),
            'provinces' => count($data->provinces),
            'sectors' => $data->sectorsCount(),
        ];
    }

    /**
     * Barra e legenda dei km nel territorio per stato di accatastamento (1 → 4), con la quota dei consolidati (3–4).
     *
     * @return array{segments: list<array{status: CaiTrailStatus, width: float, label: string, title: string, km: string, share: string, count: string}>, consolidatedOffset: float, consolidatedWidth: float, consolidatedShare: string, consolidatedKm: string}
     */
    public function statusSummary(CaiRegionalGroupTrailsData $data): array
    {
        $stats = $data->territory;
        $total = $stats->km();
        $segments = [];

        foreach (CaiTrailStatus::cases() as $status) {
            $km = $stats->kmOf($status);
            $percent = $total > 0 ? $km / $total * 100 : 0.0;
            $segments[] = [
                'status' => $status,
                'width' => round($percent, 2),
                'label' => $percent > self::BAR_LABEL_MIN_PERCENT ? self::kmRounded($km).' km' : '',
                'title' => $status->getLabel().' · '.self::kmRounded($km).' km',
                'km' => self::kmRounded($km),
                'share' => $total > 0 ? self::percent($km / $total) : '—',
                'count' => self::trailsNoun($stats->countOf($status)),
            ];
        }

        $offset = $segments[0]['width'] + $segments[1]['width'];

        return [
            'segments' => $segments,
            'consolidatedOffset' => $offset,
            'consolidatedWidth' => $total > 0 ? round(100 - $offset, 2) : 0.0,
            'consolidatedShare' => self::percent($stats->consolidatedShare()),
            'consolidatedKm' => self::kmRounded($stats->consolidatedKm()),
        ];
    }

    /**
     * Km e numero dei sentieri presenti per percorribilità.
     *
     * @return list<array{practicability: CaiTrailPracticability, km: string, count: string}>
     */
    public function practicabilitySummary(CaiRegionalGroupTrailsData $data): array
    {
        return array_map(fn (CaiTrailPracticability $practicability): array => [
            'practicability' => $practicability,
            'km' => self::kmRounded($data->practicability[$practicability->value]['km'] ?? 0.0),
            'count' => self::trailsNoun($data->practicability[$practicability->value]['count'] ?? 0),
        ], CaiTrailPracticability::cases());
    }

    // ---- Per provincia ----

    public function isOpen(CaiRegionalGroupTrailProvinceRow $province): bool
    {
        return $this->openProvince === (string) $province->id;
    }

    /** "1 senza sentieri" / "3 senza sentieri". */
    public static function emptySectorsLabel(CaiRegionalGroupTrailProvinceRow $province): string
    {
        return $province->emptySectorsCount().' senza sentieri';
    }

    /** "1 settore senza sentieri censiti" / "3 settori senza sentieri censiti". */
    public static function emptySectorsNote(CaiRegionalGroupTrailProvinceRow $province): string
    {
        $count = $province->emptySectorsCount();

        return $count.' '.($count === 1 ? 'settore' : 'settori').' senza sentieri censiti';
    }

    /** Codice del settore da mostrare ("LBG01"; `code` della fonte è solo il progressivo). */
    public static function sectorCode(CaiRegionalGroupTrailSectorRow $sector): string
    {
        return $sector->fullCode ?? $sector->code ?? '—';
    }

    /** Nome del settore, `null` se coincide col codice (molti settori osm2cai non hanno un nome). */
    public static function sectorName(CaiRegionalGroupTrailSectorRow $sector): ?string
    {
        return filled($sector->name) && $sector->name !== self::sectorCode($sector) ? $sector->name : null;
    }

    public static function mapAreaId(CaiRegionalGroupTrailSectorRow $sector): string
    {
        return CaiMapArea::sectorId($sector->id);
    }

    /**
     * Celle dei km per stato (1 → 4), "—" se zero.
     *
     * @return list<string|null>
     */
    public static function kmCells(CaiTrailKmStats $stats): array
    {
        return array_map(
            fn (CaiTrailStatus $status): ?string => $stats->kmOf($status) > 0 ? self::kmRounded($stats->kmOf($status)) : null,
            CaiTrailStatus::cases(),
        );
    }

    /**
     * Barra sottile dei km per stato: larghezze in % del totale.
     *
     * @return list<array{color: string, width: float}>
     */
    public static function bar(CaiTrailKmStats $stats): array
    {
        $total = $stats->km();

        return array_map(fn (CaiTrailStatus $status): array => [
            'color' => $status->color(),
            'width' => $total > 0 ? round($stats->kmOf($status) / $total * 100, 2) : 0.0,
        ], CaiTrailStatus::cases());
    }

    /** Quota dei km consolidati (3–4), "—" senza km. */
    public static function consolidated(CaiTrailKmStats $stats): string
    {
        return self::percent($stats->consolidatedShare());
    }

    // ---- Per Sezione ----

    /**
     * Righe della tabella "Per Sezione" con ricerca, filtro e ordinamento correnti; a parità per nome.
     *
     * @return list<CaiRegionalGroupTrailSectionRow>
     */
    public function sectionRows(CaiRegionalGroupTrailsData $data): array
    {
        $search = mb_strtolower(trim($this->sectionSearch));
        $rows = array_values(array_filter($data->sections, function (CaiRegionalGroupTrailSectionRow $row) use ($search): bool {
            $count = $row->stats->trailsCount();
            $matchesFilter = match ($this->sectionFilter) {
                self::SECTIONS_WITH_TRAILS => $count > 0,
                self::SECTIONS_WITHOUT_TRAILS => $count === 0,
                default => true,
            };

            return $matchesFilter && ($search === ''
                || str_contains(mb_strtolower($row->name), $search)
                || str_contains(mb_strtolower(self::sectionName($row)), $search));
        }));

        $direction = $this->sectionSortDescending ? -1 : 1;
        $byName = fn (CaiRegionalGroupTrailSectionRow $a, CaiRegionalGroupTrailSectionRow $b): int => strnatcasecmp(self::sectionName($a), self::sectionName($b));

        usort($rows, fn (CaiRegionalGroupTrailSectionRow $a, CaiRegionalGroupTrailSectionRow $b): int => match ($this->sectionSort) {
            'nome' => $direction * $byName($a, $b),
            'sentieri' => $direction * ($a->stats->trailsCount() <=> $b->stats->trailsCount()) ?: $byName($a, $b),
            default => $direction * ($a->stats->km() <=> $b->stats->km()) ?: $byName($a, $b),
        });

        return $rows;
    }

    /**
     * Pulsanti del filtro della tabella "Per Sezione", con il numero di Sezioni.
     *
     * @return list<array{key: string, label: string, short: string, count: int}>
     */
    public function sectionChips(CaiRegionalGroupTrailsData $data): array
    {
        $total = count($data->sections);
        $with = count(array_filter($data->sections, fn (CaiRegionalGroupTrailSectionRow $row): bool => $row->stats->trailsCount() > 0));

        return [
            ['key' => self::SECTIONS_ALL, 'label' => 'Tutte', 'short' => 'Tutte', 'count' => $total],
            ['key' => self::SECTIONS_WITH_TRAILS, 'label' => 'Con sentieri', 'short' => 'Con sentieri', 'count' => $with],
            ['key' => self::SECTIONS_WITHOUT_TRAILS, 'label' => 'Senza sentieri', 'short' => 'Senza', 'count' => $total - $with],
        ];
    }

    /** "152 Sezioni del GR · 8 senza sentieri assegnati" oppure "… · tutte con almeno un sentiero". */
    public function sectionsSubtitle(CaiRegionalGroupTrailsData $data): string
    {
        $total = count($data->sections);
        $without = count(array_filter($data->sections, fn (CaiRegionalGroupTrailSectionRow $row): bool => $row->stats->trailsCount() === 0));

        return $total.' '.($total === 1 ? 'Sezione' : 'Sezioni').' del GR · '
            .($without > 0 ? $without.' senza sentieri assegnati' : 'tutte con almeno un sentiero');
    }

    /** Testo quando la ricerca o il filtro non lasciano Sezioni. */
    public function noSectionsText(CaiRegionalGroupTrailsData $data): string
    {
        if ($data->sections === []) {
            return 'Nessuna Sezione collegata al '.$data->groupName.'.';
        }

        return $this->sectionFilter === self::SECTIONS_WITHOUT_TRAILS && trim($this->sectionSearch) === ''
            ? 'Tutte le Sezioni del '.$data->groupName.' hanno almeno un sentiero assegnato.'
            : 'Nessuna Sezione corrisponde alla ricerca.';
    }

    /**
     * Riga "Somma delle Sezioni": sentieri, condivisi, km (che superano i km gestiti di `difference` quando ci sono
     * sentieri condivisi) e km per stato.
     *
     * @return array{trails: string, shared: string, km: string, difference: string, exceeds: bool, kmCells: list<string>}
     */
    public function sectionsTotal(CaiRegionalGroupTrailsData $data): array
    {
        $sum = $data->sectionsKm();
        $difference = $sum - $data->managedKm;

        return [
            'trails' => ItalianNumberFormatter::integer(array_sum(array_map(fn (CaiRegionalGroupTrailSectionRow $row): int => $row->stats->trailsCount(), $data->sections))),
            'shared' => ItalianNumberFormatter::integer(array_sum(array_map(fn (CaiRegionalGroupTrailSectionRow $row): int => $row->sharedCount, $data->sections))),
            'km' => self::kmRounded($sum),
            'difference' => self::kmRounded($difference),
            'exceeds' => $difference >= 0.5,
            'kmCells' => array_map(
                fn (CaiTrailStatus $status): string => self::kmRounded(array_sum(array_map(fn (CaiRegionalGroupTrailSectionRow $row): float => $row->stats->kmOf($status), $data->sections))),
                CaiTrailStatus::cases(),
            ),
        ];
    }

    /** Nome breve della Sezione ("CAI Bergamo"). */
    public static function sectionName(CaiRegionalGroupTrailSectionRow $row): string
    {
        return CaiSectionShortName::of($row->name);
    }

    /** Elenco dei sentieri della Sezione visto dal GR. */
    public function sectionUrl(CaiRegionalGroupTrailSectionRow $row): string
    {
        return RegionalGroupSectionTrails::getUrl(['codice' => $row->codiceCai]);
    }

    // ---- Elenco ----

    public function activeProvince(): ?CaiRegionalGroupTrailProvinceRow
    {
        return $this->findProvince($this->province);
    }

    public function activeSector(): ?CaiRegionalGroupTrailSectorRow
    {
        if ($this->sector === null || ! ctype_digit($this->sector)) {
            return null;
        }

        $province = $this->provinceOfSector((int) $this->sector);
        $active = $this->activeProvince();

        if ($province === null || ($active !== null && $active->id !== $province->id)) {
            return null;
        }

        foreach ($province->sectors as $sector) {
            if ($sector->id === (int) $this->sector) {
                return $sector;
            }
        }

        return null;
    }

    public function activeSection(): ?string
    {
        return $this->validSection($this->section);
    }

    public function activeStatus(): ?CaiTrailStatus
    {
        return $this->status === null ? null : CaiTrailStatus::tryFrom((int) $this->status);
    }

    public function activePracticability(): ?CaiTrailPracticability
    {
        return CaiTrailPracticability::fromKey($this->practicability);
    }

    public function activeSort(): CaiTrailSort
    {
        return CaiTrailSort::tryFrom((string) $this->sort) ?? CaiTrailSort::NumberAsc;
    }

    public function filters(): CaiRegionalGroupTrailFilters
    {
        return new CaiRegionalGroupTrailFilters(
            provinceId: $this->activeProvince()?->id,
            sectorId: $this->activeSector()?->id,
            section: $this->activeSection(),
            status: $this->activeStatus(),
            practicability: $this->activePracticability(),
            search: mb_substr($this->search, 0, 100),
            sort: $this->activeSort(),
        );
    }

    /**
     * Pagina corrente dell'elenco (una pagina oltre l'ultima mostra l'ultima).
     *
     * @return LengthAwarePaginator<int, CaiRegionalGroupTrailRow>
     */
    public function trails(): LengthAwarePaginator
    {
        if ($this->trails !== null) {
            return $this->trails;
        }

        $region = $this->region();

        if ($region === null) {
            /** @var LengthAwarePaginator<int, CaiRegionalGroupTrailRow> $empty */
            $empty = new LengthAwarePaginator([], 0, self::PER_PAGE, 1);

            return $this->trails = $empty;
        }

        $page = ctype_digit((string) $this->listPage) ? max(1, (int) $this->listPage) : 1;
        $trails = CaiRegionalGroupTrails::trails($region, $this->filters(), self::PER_PAGE, $page);

        if ($page > $trails->lastPage()) {
            $trails = CaiRegionalGroupTrails::trails($region, $this->filters(), self::PER_PAGE, $trails->lastPage());
        }

        return $this->trails = $trails;
    }

    public function isFiltering(): bool
    {
        return $this->filters()->isFiltering();
    }

    /** "4.017 sentieri · 20.535 km" oppure, con un filtro, "12 di 4.017 sentieri · 48 km" (km di tutti i filtrati). */
    public function countLabel(CaiRegionalGroupTrailsData $data): string
    {
        $region = $this->region();
        $shown = $this->trails()->total();
        $total = $data->territory->trailsCount();
        $km = $region === null ? 0.0 : CaiRegionalGroupTrails::trailsKm($region, $this->filters());

        return ($shown === $total && ! $this->isFiltering()
            ? ItalianNumberFormatter::integer($total).' '.($total === 1 ? 'sentiero' : 'sentieri')
            : ItalianNumberFormatter::integer($shown).' di '.ItalianNumberFormatter::integer($total).' sentieri')
            .' · '.self::kmRounded($km).' km';
    }

    /**
     * Pulsanti delle pagine come nel design: prima, ultima e due per lato attorno alla corrente; `null` = "…".
     *
     * @return list<int|null>
     */
    public function pageNumbers(): array
    {
        $last = $this->trails()->lastPage();
        $current = $this->trails()->currentPage();
        $pages = [];

        for ($page = 1; $page <= $last; $page++) {
            if ($page === 1 || $page === $last || abs($page - $current) <= 2) {
                $pages[] = $page;
            } elseif (end($pages) !== null) {
                $pages[] = null;
            }
        }

        return $pages;
    }

    /** @return array<int, string> province della regione: id → nome */
    public function provinceOptions(CaiRegionalGroupTrailsData $data): array
    {
        $options = [];

        foreach ($data->provinces as $province) {
            $options[$province->id] = $province->name;
        }

        return $options;
    }

    /** @return array<int, string> settori (della provincia scelta, o tutti): id → "LBG01 · Nome", "(vuoto)" se senza sentieri */
    public function sectorOptions(CaiRegionalGroupTrailsData $data): array
    {
        $active = $this->activeProvince();
        $sectors = [];

        foreach ($data->provinces as $province) {
            if ($active === null || $active->id === $province->id) {
                array_push($sectors, ...$province->sectors);
            }
        }

        usort($sectors, fn (CaiRegionalGroupTrailSectorRow $a, CaiRegionalGroupTrailSectorRow $b): int => strnatcasecmp(self::sectorCode($a), self::sectorCode($b)));

        $options = [];

        foreach ($sectors as $sector) {
            $name = self::sectorName($sector);
            $options[$sector->id] = self::sectorCode($sector).($name === null ? '' : ' · '.$name).($sector->hasTrails ? '' : ' (vuoto)');
        }

        return $options;
    }

    /** @return array<string, string> Sezioni del GR con sentieri: codice → nome breve, per nome */
    public function sectionOptions(CaiRegionalGroupTrailsData $data): array
    {
        $options = [];

        foreach ($data->sections as $row) {
            if ($row->stats->trailsCount() > 0) {
                $options[$row->codiceCai] = self::sectionName($row);
            }
        }

        uasort($options, 'strnatcasecmp');

        return $options;
    }

    /**
     * Provincia e settore della riga ("Bergamo", "LBG01 · Val Brembana"; `sectorShort` per le card di mobile).
     *
     * @return array{province: string, sector: string, sectorShort: string}
     */
    public static function territory(CaiRegionalGroupTrailRow $row): array
    {
        $sector = $row->sector;
        $code = $sector === null ? null : ($sector->full_code ?? $sector->code);
        $name = $sector === null || blank($sector->name) || $sector->name === $code ? null : $sector->name;

        return [
            'province' => $row->province->name ?? '—',
            'sector' => $code === null ? '—' : $code.($name === null ? '' : ' · '.$name),
            'sectorShort' => $code === null ? '' : $code.($name === null ? '' : ' '.$name),
        ];
    }

    /** Prima Sezione della riga (nome breve), "Nessuna Sezione" se non ne ha. */
    public static function firstSection(CaiRegionalGroupTrailRow $row): ?string
    {
        $first = $row->sections()[0] ?? null;

        return $first === null ? null : CaiSectionShortName::of((string) $first->name);
    }

    /** Tutte le Sezioni della riga ("CAI Milano, CAI Bergamo"), `null` se non ne ha. */
    public static function allSections(CaiRegionalGroupTrailRow $row): ?string
    {
        $names = array_map(fn ($section): string => CaiSectionShortName::of((string) $section->name), $row->sections());

        return $names === [] ? null : implode(', ', $names);
    }

    /** "Condiviso con CAI Bergamo, SAT" (le Sezioni dopo la prima), vuoto se non condiviso. */
    public static function sharedWith(CaiRegionalGroupTrailRow $row): string
    {
        $names = array_map(fn ($section): string => CaiSectionShortName::of((string) $section->name), array_slice($row->sections(), 1));

        return $names === [] ? '' : 'Condiviso con '.implode(', ', $names);
    }

    /**
     * Ricerca, filtri, ordinamento e pagina correnti come parametri dell'URL ({@see LIST_QUERY_KEYS}), solo quelli
     * impostati e validi.
     *
     * @return array<string, string>
     */
    public function listQuery(): array
    {
        $page = $this->trails()->currentPage();

        return array_filter([
            'cerca' => trim($this->search) === '' ? null : mb_substr(trim($this->search), 0, 100),
            'provincia' => $this->activeProvince() === null ? null : (string) $this->activeProvince()->id,
            'settore' => $this->activeSector() === null ? null : (string) $this->activeSector()->id,
            'sezione' => $this->activeSection(),
            'stato' => $this->activeStatus() === null ? null : (string) $this->activeStatus()->value,
            'percorribilita' => $this->activePracticability()?->key(),
            'ordina' => $this->activeSort() === CaiTrailSort::NumberAsc ? null : $this->activeSort()->value,
            'pagina' => $page > 1 ? (string) $page : null,
        ], fn (?string $value): bool => $value !== null && $value !== '');
    }

    /** Scheda del sentiero aperta dal GR: `?da=gr` più i parametri dell'elenco. */
    public function trailUrl(CaiTrail $trail): string
    {
        return CaiTrailDetail::getUrl(['trailId' => $trail->id, 'da' => 'gr', ...$this->listQuery()]);
    }

    public function dashboardUrl(): string
    {
        return RegionalGroupDashboard::getUrl();
    }

    // ---- Formattazione ----

    /** Km interi con separatore delle migliaia ("20.535"), come nei totali del design. */
    public static function kmRounded(float $km): string
    {
        return ItalianNumberFormatter::integer($km);
    }

    /** Km con un decimale ("12,4"). */
    public static function km(float $km): string
    {
        return ItalianNumberFormatter::decimal($km, 1);
    }

    /** Km del sentiero con un decimale, "—" se manca. */
    public static function trailKm(CaiTrail $trail): string
    {
        return $trail->length_km === null ? '—' : self::km((float) $trail->length_km);
    }

    /** Quota 0–1 in percentuale intera ("81%"), "—" se `null`. */
    public static function percent(?float $share): string
    {
        return $share === null ? '—' : ItalianNumberFormatter::integer(round($share * 100)).'%';
    }

    public static function trailsNoun(int $count): string
    {
        return ItalianNumberFormatter::integer($count).' '.($count === 1 ? 'sentiero' : 'sentieri');
    }

    private function findProvince(?string $id): ?CaiRegionalGroupTrailProvinceRow
    {
        if ($id === null || ! ctype_digit($id)) {
            return null;
        }

        foreach ($this->data()->provinces ?? [] as $province) {
            if ($province->id === (int) $id) {
                return $province;
            }
        }

        return null;
    }

    private function provinceOfSector(int $sectorId): ?CaiRegionalGroupTrailProvinceRow
    {
        foreach ($this->data()->provinces ?? [] as $province) {
            foreach ($province->sectors as $sector) {
                if ($sector->id === $sectorId) {
                    return $province;
                }
            }
        }

        return null;
    }

    /** Codice di una Sezione del GR, oppure "nessuna"; altro → `null`. */
    private function validSection(?string $key): ?string
    {
        if ($key === CaiRegionalGroupTrailFilters::WITHOUT_SECTION) {
            return $key;
        }

        foreach ($this->data()->sections ?? [] as $row) {
            if ($row->codiceCai === $key) {
                return $key;
            }
        }

        return null;
    }
}
