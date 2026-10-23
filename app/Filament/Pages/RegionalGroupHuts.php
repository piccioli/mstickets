<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\CaiDirectory\Enums\CaiHutStatus;
use App\Domain\CaiDirectory\Enums\CaiHutType;
use App\Domain\CaiDirectory\Enums\CaiRegionalGroupHutSortColumn;
use App\Domain\CaiDirectory\Models\CaiHut;
use App\Domain\CaiDirectory\Queries\CaiRegionalGroupHuts;
use App\Domain\CaiDirectory\Support\CaiMapMarker;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupHutFilters;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupHutRow;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupHutsData;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Support\Formatting\ItalianNumberFormatter;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

/**
 * Rifugi del GR (Fase 10.5, US-1043), design `docs/project/design/fase-10/rifugi-del-gr.dc.html` §1: riepilogo (totale,
 * in attività, posti letto, Sezioni con rifugi su M, contratti in scadenza/scaduti), mappa con cluster, elenco
 * filtrabile, ordinabile e raggruppabile per Sezione, riquadro "Sezioni senza rifugi". Sostituisce la pagina "in arrivo"
 * della 10.2 mantenendo classe e slug (il badge "Rifugi" della Dashboard GR punta qui).
 *
 * Dati da {@see CaiRegionalGroupHuts} per la `region` dell'utente; filtri, ordinamento e raggruppamento vivono
 * nell'URL (`?sezione=9219001&tipo=bivacco&stato=in-attivita&contratti=1&ordina=quota&verso=asc&raggruppa=0`, solo ciò
 * che differisce dal default) e si applicano in PHP con {@see CaiRegionalGroupHutFilters}; un valore non riconosciuto
 * vale come assente. Gli stessi parametri passano alla scheda del rifugio (con `da=gr`), che li riporta indietro.
 *
 * Fuori dalla navigazione (si raggiunge dal badge). Gate: solo un customer con `customer_type = GruppoRegionale`.
 */
class RegionalGroupHuts extends Page
{
    /** Card mostrate su mobile prima di "Mostra altri N". */
    public const MOBILE_PAGE_SIZE = 12;

    /** Oltre questo numero di rifugi l'elenco è raggruppato per Sezione di default. */
    public const GROUPED_BY_DEFAULT_ABOVE = 40;

    /** Nome della mappa negli eventi `cai-map-*` (vedi `x-cai.map`). */
    public const MAP_NAME = 'gr-huts';

    protected string $view = 'filament.pages.regional-group-huts';

    protected static ?string $title = 'Rifugi del GR';

    protected static ?string $slug = 'gr/rifugi';

    protected static bool $shouldRegisterNavigation = false;

    /** Codice CAI della Sezione o `gr` (rifugi intestati al GR), `null` = tutte. */
    #[Url(as: 'sezione')]
    public ?string $section = null;

    /** Chiave del tipo ({@see CaiHutType::key()}), `null` = tutti. */
    #[Url(as: 'tipo')]
    public ?string $type = null;

    /** Chiave dello stato ({@see CaiHutStatus::key()}), `null` = tutti. */
    #[Url(as: 'stato')]
    public ?string $status = null;

    /** `1` = solo i contratti in scadenza o scaduti. */
    #[Url(as: 'contratti')]
    public ?string $contracts = null;

    /** Colonna di ordinamento ({@see CaiRegionalGroupHutSortColumn}), `null` = default (nome, o scadenza col filtro). */
    #[Url(as: 'ordina')]
    public ?string $sort = null;

    /** `asc`/`desc`; `null` = verso del primo clic della colonna. */
    #[Url(as: 'verso')]
    public ?string $direction = null;

    /** `1`/`0`; `null` = default (raggruppato se il GR ha più di {@see GROUPED_BY_DEFAULT_ABOVE} rifugi). */
    #[Url(as: 'raggruppa')]
    public ?string $group = null;

    /** Mobile: tutte le card invece delle prime {@see MOBILE_PAGE_SIZE}. */
    public bool $showAll = false;

    private bool $dataResolved = false;

    private ?CaiRegionalGroupHutsData $data = null;

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

    public function filterSection(?string $key): void
    {
        $this->section = $key === null || $key === '' ? null : $key;
        $this->showAll = false;
    }

    public function filterType(?string $key): void
    {
        $this->type = CaiHutType::fromKey($key)?->key();
        $this->showAll = false;
    }

    public function filterStatus(?string $key): void
    {
        $this->status = CaiHutStatus::fromKey($key)?->key();
        $this->showAll = false;
    }

    /** Card "Contratti di gestione" e pillola dei filtri: attiva/disattiva e torna all'ordinamento di default. */
    public function toggleContracts(): void
    {
        $this->contracts = $this->contractsFilterActive() ? null : '1';
        $this->sort = null;
        $this->direction = null;
        $this->showAll = false;
    }

    public function resetFilters(): void
    {
        $this->section = null;
        $this->type = null;
        $this->status = null;
        $this->contracts = null;
        $this->showAll = false;
    }

    /** Primo clic sulla colonna: verso di default; clic sulla colonna già attiva: inverte. */
    public function sortBy(string $column): void
    {
        $column = CaiRegionalGroupHutSortColumn::tryFrom($column);

        if ($column === null) {
            return;
        }

        $this->applySort($column, $column === $this->sortColumn() ? ! $this->sortDescending() : $column->defaultDescending());
    }

    /** Menu "Ordina" di mobile: `{colonna}:{asc|desc}`. */
    public function applySortOption(string $option): void
    {
        [$column, $direction] = array_pad(explode(':', $option, 2), 2, null);
        $column = CaiRegionalGroupHutSortColumn::tryFrom((string) $column);

        if ($column === null || ! in_array($direction, ['asc', 'desc'], true)) {
            return;
        }

        $this->applySort($column, $direction === 'desc');
    }

    public function toggleGrouping(): void
    {
        $grouped = ! $this->isGrouped();

        $this->group = $grouped === $this->groupedByDefault() ? null : ($grouped ? '1' : '0');
    }

    public function region(): ?Region
    {
        $user = Auth::user();

        return $user instanceof User ? $user->region : null;
    }

    /** Rifugi e riepilogo della regione dell'utente, `null` se l'utente non ha regione. */
    public function data(): ?CaiRegionalGroupHutsData
    {
        if ($this->dataResolved) {
            return $this->data;
        }

        $this->dataResolved = true;
        $region = $this->region();

        return $this->data = $region === null ? null : CaiRegionalGroupHuts::forRegion($region);
    }

    /** @return list<CaiRegionalGroupHutRow> */
    public function allRows(): array
    {
        return $this->data()->huts ?? [];
    }

    public function activeSection(): ?string
    {
        $keys = array_map(CaiRegionalGroupHutFilters::sectionKey(...), $this->allRows());

        return $this->section !== null && in_array($this->section, $keys, true) ? $this->section : null;
    }

    public function activeType(): ?CaiHutType
    {
        return CaiHutType::fromKey($this->type);
    }

    public function activeStatus(): ?CaiHutStatus
    {
        return CaiHutStatus::fromKey($this->status);
    }

    public function contractsFilterActive(): bool
    {
        return $this->contracts === '1';
    }

    public function sortColumn(): CaiRegionalGroupHutSortColumn
    {
        return CaiRegionalGroupHutSortColumn::tryFrom((string) $this->sort) ?? $this->defaultSortColumn();
    }

    public function sortDescending(): bool
    {
        return match ($this->direction) {
            'asc' => false,
            'desc' => true,
            default => $this->sortColumn()->defaultDescending(),
        };
    }

    /** Valore del menu "Ordina" di mobile corrispondente all'ordinamento attivo. */
    public function sortOption(): string
    {
        return $this->sortColumn()->value.':'.($this->sortDescending() ? 'desc' : 'asc');
    }

    /** @return array<string, string> opzioni del menu "Ordina" di mobile, come nel design */
    public static function sortOptions(): array
    {
        return [
            'nome:asc' => 'Nome A–Z',
            'sezione:asc' => 'Sezione A–Z',
            'quota:desc' => 'Quota più alta',
            'posti:desc' => 'Più posti letto',
            'scadenza:asc' => 'Scadenza più vicina',
        ];
    }

    public function filters(): CaiRegionalGroupHutFilters
    {
        return new CaiRegionalGroupHutFilters(
            section: $this->activeSection(),
            type: $this->activeType(),
            status: $this->activeStatus(),
            contractAlerts: $this->contractsFilterActive(),
            sort: $this->sortColumn(),
            descending: $this->sortDescending(),
        );
    }

    /** @return list<CaiRegionalGroupHutRow> rifugi filtrati e ordinati */
    public function rows(): array
    {
        return $this->filters()->apply($this->allRows());
    }

    /** "Raggruppa per Sezione" si offre solo con almeno 2 gruppi e senza filtro per Sezione. */
    public function groupingAvailable(): bool
    {
        return CaiRegionalGroupHutFilters::groupsCount($this->allRows()) >= 2 && $this->activeSection() === null;
    }

    public function isGrouped(): bool
    {
        if (! $this->groupingAvailable()) {
            return false;
        }

        return match ($this->group) {
            '1' => true,
            '0' => false,
            default => $this->groupedByDefault(),
        };
    }

    /**
     * Voci dell'elenco: le righe, precedute dalla testata di ogni gruppo quando l'elenco è raggruppato. Con `$limit`
     * (card di mobile) al più quel numero di righe, senza una testata rimasta senza righe in fondo.
     *
     * @param  list<CaiRegionalGroupHutRow>  $rows
     * @return list<array{header: array{key: string, title: string, count: string}|null, row: CaiRegionalGroupHutRow|null}>
     */
    public function items(array $rows, ?int $limit = null): array
    {
        $items = [];
        $groups = $this->isGrouped()
            ? CaiRegionalGroupHutFilters::groups($rows)
            : [['key' => '', 'title' => '', 'rows' => $rows]];
        $shown = 0;

        foreach ($groups as $group) {
            if ($group['title'] !== '') {
                $count = count($group['rows']);
                $items[] = ['header' => ['key' => $group['key'], 'title' => $group['title'], 'count' => $count.' '.($count === 1 ? 'rifugio' : 'rifugi')], 'row' => null];
            }

            foreach ($group['rows'] as $row) {
                if ($limit !== null && $shown >= $limit) {
                    break 2;
                }

                $items[] = ['header' => null, 'row' => $row];
                $shown++;
            }
        }

        while ($items !== [] && $items[array_key_last($items)]['row'] === null) {
            array_pop($items);
        }

        return $items;
    }

    /** @return array<string, string> Sezioni con rifugi (e "GR {regione}"): chiave → "Nome (n)", per nome */
    public function sectionOptions(): array
    {
        $options = [];

        foreach ($this->allRows() as $row) {
            $key = CaiRegionalGroupHutFilters::sectionKey($row);
            $options[$key] ??= ['name' => $row->sectionName, 'count' => 0];
            $options[$key]['count']++;
        }

        uasort($options, fn (array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));

        return array_map(fn (array $option): string => $option['name'].' ('.$option['count'].')', $options);
    }

    /** @return array<string, string> tipi presenti: chiave → "Etichetta (n)", nell'ordine dell'enum */
    public function typeOptions(): array
    {
        $options = [];

        foreach (CaiHutType::cases() as $type) {
            $count = count(array_filter($this->allRows(), fn (CaiRegionalGroupHutRow $row): bool => $row->hut->type === $type));

            if ($count > 0) {
                $options[$type->key()] = $type->getLabel().' ('.$count.')';
            }
        }

        return $options;
    }

    /** @return array<string, string> stati presenti: chiave → "Etichetta (n)", nell'ordine dell'enum */
    public function statusOptions(): array
    {
        $options = [];

        foreach (CaiHutStatus::cases() as $status) {
            $count = count(array_filter($this->allRows(), fn (CaiRegionalGroupHutRow $row): bool => $row->hut->status === $status));

            if ($count > 0) {
                $options[$status->key()] = $status->getLabel().' ('.$count.')';
            }
        }

        return $options;
    }

    public function contractAlertsCount(): int
    {
        $data = $this->data();

        return $data === null ? 0 : $data->expiringContracts + $data->expiredContracts;
    }

    /** "193 rifugi · 66 Sezioni" oppure, con un filtro, "n di N rifugi". */
    public function countLabel(int $shown): string
    {
        $data = $this->data();
        $total = $data->total ?? 0;
        $sections = $data->sectionsWithHutsCount ?? 0;

        if ($this->filters()->isFiltering()) {
            return $shown.' di '.ItalianNumberFormatter::integer($total).' rifugi';
        }

        return ItalianNumberFormatter::integer($total).' '.($total === 1 ? 'rifugio' : 'rifugi')
            .' · '.$sections.' '.($sections === 1 ? 'Sezione' : 'Sezioni');
    }

    /** "GR Toscana · Codice CAI 9900000", senza il codice se la regione non ha Sezioni. */
    public function subtitle(CaiRegionalGroupHutsData $data): string
    {
        return $data->codiceCai === null ? $data->groupName : $data->groupName.' · Codice CAI '.$data->codiceCai;
    }

    /** Data dell'ultimo aggiornamento dei dati di rifugi.cai.it fra i rifugi del GR. */
    public function lastUpdate(): ?Carbon
    {
        $latest = null;

        foreach ($this->allRows() as $row) {
            $timestamp = $row->hut->source_updated_at?->getTimestamp();
            $latest = $timestamp === null ? $latest : max($latest ?? $timestamp, $timestamp);
        }

        return $latest === null ? null : Carbon::createFromTimestamp($latest, config('app.timezone'));
    }

    /**
     * @param  list<CaiRegionalGroupHutRow>  $rows
     * @return list<CaiMapMarker>
     */
    public function markers(array $rows): array
    {
        return array_values(array_filter(array_map(
            fn (CaiRegionalGroupHutRow $row): ?CaiMapMarker => CaiMapMarker::forHut($row->hut, $this->hutUrl($row->hut), $row->sectionName),
            $rows,
        )));
    }

    /**
     * Parametri dell'elenco da conservare (filtri, ordinamento, raggruppamento), solo quelli valorizzati: passano alla
     * scheda del rifugio, che li usa per il link di ritorno.
     *
     * @return array<string, string>
     */
    public function listQuery(): array
    {
        return array_filter([
            'sezione' => $this->section,
            'tipo' => $this->type,
            'stato' => $this->status,
            'contratti' => $this->contracts,
            'ordina' => $this->sort,
            'verso' => $this->direction,
            'raggruppa' => $this->group,
        ], fn (?string $value): bool => $value !== null && $value !== '');
    }

    /** Scheda del rifugio aperta dal GR: `?da=gr` più i parametri dell'elenco. */
    public function hutUrl(CaiHut $hut): string
    {
        return CaiHutDetail::getUrl(['idCai' => $hut->id_cai, 'da' => 'gr', ...$this->listQuery()]);
    }

    /** Prima foto del rifugio (URL di rifugi.cai.it), `null` se non ne ha. */
    public static function photo(CaiHut $hut): ?string
    {
        return SectionHuts::photo($hut);
    }

    public static function number(?int $value): string
    {
        return $value === null ? '—' : ItalianNumberFormatter::integer($value);
    }

    public function dashboardUrl(): string
    {
        return RegionalGroupDashboard::getUrl();
    }

    private function defaultSortColumn(): CaiRegionalGroupHutSortColumn
    {
        return $this->contractsFilterActive() ? CaiRegionalGroupHutSortColumn::ContractEnd : CaiRegionalGroupHutSortColumn::Name;
    }

    private function groupedByDefault(): bool
    {
        return count($this->allRows()) > self::GROUPED_BY_DEFAULT_ABOVE;
    }

    /** Nell'URL solo ciò che differisce dal default: niente `ordina` per la colonna di default, niente `verso` per il primo clic. */
    private function applySort(CaiRegionalGroupHutSortColumn $column, bool $descending): void
    {
        $this->sort = $column === $this->defaultSortColumn() ? null : $column->value;
        $this->direction = $descending === $column->defaultDescending() ? null : ($descending ? 'desc' : 'asc');
    }
}
