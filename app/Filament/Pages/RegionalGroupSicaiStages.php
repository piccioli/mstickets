<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\CaiDirectory\Enums\CaiTrailPracticability;
use App\Domain\CaiDirectory\Queries\CaiRegionalGroupDashboardMetrics;
use App\Domain\CaiDirectory\Queries\CaiRegionalGroupSicai;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupSicaiData;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupSicaiRestPointRow;
use App\Domain\CaiDirectory\Support\CaiRegionalGroupSicaiStageRow;
use App\Domain\CaiDirectory\Support\CaiSicaiRestPointType;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Region;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Widgets\RegionalGroupSicaiStagesMap;
use App\Support\Formatting\ItalianNumberFormatter;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

/**
 * Tappe SICAI del GR (Fase 10.9, US-1095), design `docs/project/design/fase-10/tappe-sicai-del-gr.dc.html`: riepilogo
 * (tappe, km e dislivello delle principali, punti di appoggio, tappe con criticità), mappa con il tracciato a colori
 * alternati e i punti di appoggio per tipo (widget {@see RegionalGroupSicaiStagesMap}, componente figlio: i tracciati
 * non viaggiano a ogni filtro), elenco delle tappe filtrabile per percorribilità e cercabile per codice o località,
 * elenco dei punti di appoggio per tipo. Sostituisce la pagina "in arrivo" della 10.2 mantenendo classe e slug (il badge
 * "Tappe SICAI" della Dashboard GR punta qui).
 *
 * Dati da {@see CaiRegionalGroupSicai} per la `region` dell'utente (lettere condivise: Toscana ed Emilia-Romagna vedono
 * le stesse tappe L). Filtri nell'URL (`?percorribilita=non-percorribile&cerca=E1&tipo=rifugio`); un valore non
 * riconosciuto vale come assente. Il clic su una tappa apre la scheda del sentiero con `?da=sicai`.
 *
 * Fuori dalla navigazione. Gate: solo un customer con `customer_type = GruppoRegionale`.
 */
class RegionalGroupSicaiStages extends Page
{
    /** Card delle tappe mostrate su mobile prima di "Mostra tutte le N tappe". */
    public const MOBILE_STAGES = 10;

    /** Card dei punti di appoggio mostrate su mobile prima di "Mostra tutti i N punti". */
    public const MOBILE_POINTS = 6;

    /** Nome della mappa negli eventi `cai-map-*` (vedi `x-cai.map`). */
    public const MAP_NAME = 'gr-sicai';

    /** Ancora dell'elenco dei punti di appoggio (card del riepilogo, anteprima del punto sulla mappa). */
    public const POINTS_ANCHOR = 'gr-sicai-points';

    protected string $view = 'filament.pages.regional-group-sicai-stages';

    protected static ?string $title = 'Tappe SICAI';

    protected static ?string $slug = 'gr/tappe-sicai';

    protected static bool $shouldRegisterNavigation = false;

    /** Chiave della percorribilità ({@see CaiTrailPracticability::key()}), `null` = tutte. */
    #[Url(as: 'percorribilita')]
    public ?string $practicability = null;

    /** Ricerca per inizio del codice o per località di partenza/arrivo. */
    #[Url(as: 'cerca')]
    public string $search = '';

    /** Chiave del tipo di punto di appoggio ({@see CaiSicaiRestPointType::key()}), `null` = tutti. */
    #[Url(as: 'tipo')]
    public ?string $pointType = null;

    /** Mobile: tutte le tappe invece delle prime {@see MOBILE_STAGES}. */
    public bool $showAllStages = false;

    /** Mobile: tutti i punti invece dei primi {@see MOBILE_POINTS}. */
    public bool $showAllPoints = false;

    private bool $dataResolved = false;

    private ?CaiRegionalGroupSicaiData $data = null;

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
        $this->showAllStages = false;
    }

    public function filterPracticability(?string $key): void
    {
        $this->practicability = CaiTrailPracticability::fromKey($key)?->key();
        $this->showAllStages = false;
    }

    public function filterPointType(?string $key): void
    {
        $this->pointType = $key === null || $key === '' ? null : $key;
        $this->showAllPoints = false;
    }

    public function resetFilters(): void
    {
        $this->practicability = null;
        $this->search = '';
        $this->showAllStages = false;
    }

    public function region(): ?Region
    {
        $user = Auth::user();

        return $user instanceof User ? $user->region : null;
    }

    /** Tappe, punti e riepilogo della regione dell'utente, `null` se l'utente non ha regione. */
    public function data(): ?CaiRegionalGroupSicaiData
    {
        if ($this->dataResolved) {
            return $this->data;
        }

        $this->dataResolved = true;
        $region = $this->region();

        return $this->data = $region === null ? null : CaiRegionalGroupSicai::forRegion($region);
    }

    /** "GR Piemonte · Codice CAI 9100000 · Sentiero Italia CAI in Piemonte" (senza codice se la regione non ha Sezioni). */
    public function subtitle(CaiRegionalGroupSicaiData $data, Region $region): string
    {
        $prefix = CaiRegionalGroupDashboardMetrics::prefixOf($region);

        return $data->groupName.($prefix === null ? '' : ' · Codice CAI '.$prefix.'000').' · Sentiero Italia CAI in '.$region->label();
    }

    // ---- Riepilogo ----

    /**
     * Testi delle card del riepilogo, come nel design. Il dislivello osm2cai manca per molte tappe: la nota dice su quante
     * è sommato.
     *
     * @return array{stages: string, stagesNote: string, stagesNoteShort: string, km: string, kmNote: string, kmNoteShort: string, ascent: string, ascentNote: string, points: string, pointsNote: string}
     */
    public function summary(CaiRegionalGroupSicaiData $data): array
    {
        $variants = $data->variantsCount;
        $withAscent = $data->mainWithAscentCount;

        return [
            'stages' => ItalianNumberFormatter::integer($data->stagesCount()),
            'stagesNote' => $variants > 0
                ? $data->mainCount.' '.($data->mainCount === 1 ? 'tappa principale' : 'tappe principali').' · '.$variants.' '.($variants === 1 ? 'variante' : 'varianti')
                : $data->mainCount.' '.($data->mainCount === 1 ? 'tappa principale' : 'tappe principali').', nessuna variante',
            'stagesNoteShort' => $variants > 0 ? $data->mainCount.' + '.$variants.' '.($variants === 1 ? 'variante' : 'varianti') : 'nessuna variante',
            'km' => ItalianNumberFormatter::integer($data->mainKm),
            'kmNote' => $variants > 0 ? 'Tappe principali · più '.ItalianNumberFormatter::integer($data->variantsKm).' km di varianti' : 'Tappe principali',
            'kmNoteShort' => $variants > 0 ? '+ '.ItalianNumberFormatter::integer($data->variantsKm).' km di varianti' : 'tappe principali',
            'ascent' => $withAscent === 0 ? '—' : ItalianNumberFormatter::integer($data->mainAscent),
            'ascentNote' => match (true) {
                $withAscent === 0 => 'Dato non disponibile in osm2cai per queste tappe',
                $withAscent < $data->mainCount => 'In salita, su '.$withAscent.' di '.$data->mainCount.' tappe · in discesa '.ItalianNumberFormatter::integer($data->mainDescent).' m',
                default => 'In salita · in discesa '.ItalianNumberFormatter::integer($data->mainDescent).' m',
            },
            'points' => ItalianNumberFormatter::integer($data->restPointsCount),
            'pointsNote' => $data->restPointsCount === 0
                ? 'Nessun punto di appoggio censito nella regione'
                : implode(' · ', array_map(
                    fn (string $type, array $rows): string => CaiSicaiRestPointType::of($type)->count(count($rows)),
                    array_keys($data->restPointsByType),
                    $data->restPointsByType,
                )),
        ];
    }

    public static function notPracticableTitle(int $count): string
    {
        return $count === 1 ? '1 tappa non percorribile' : $count.' tappe non percorribili';
    }

    public static function withDifficultyTitle(int $count): string
    {
        return $count === 1 ? '1 tappa percorribile con difficoltà' : $count.' tappe percorribili con difficoltà';
    }

    // ---- Elenco delle tappe ----

    public function activePracticability(): ?CaiTrailPracticability
    {
        return CaiTrailPracticability::fromKey($this->practicability);
    }

    /** @return list<CaiRegionalGroupSicaiStageRow> tappe che rispettano ricerca e filtro */
    public function stageRows(): array
    {
        $practicability = $this->activePracticability();

        return array_values(array_filter(
            $this->data()->stages ?? [],
            fn (CaiRegionalGroupSicaiStageRow $row): bool => ($practicability === null || $row->practicability() === $practicability)
                && $this->matchesSearch($row),
        ));
    }

    /**
     * Pillole del filtro per percorribilità, con il numero di tappe che rispettano la ricerca.
     *
     * @return list<array{key: string|null, label: string, shortLabel: string, count: int, dot: string|null, active: bool}>
     */
    public function practicabilityChips(): array
    {
        $rows = array_values(array_filter($this->data()->stages ?? [], $this->matchesSearch(...)));
        $active = $this->activePracticability();
        $chips = [['key' => null, 'label' => 'Tutte', 'shortLabel' => 'Tutte', 'count' => count($rows), 'dot' => null, 'active' => $active === null]];
        $short = [
            CaiTrailPracticability::Practicable->value => 'Percorribili',
            CaiTrailPracticability::WithDifficulty->value => 'Con difficoltà',
            CaiTrailPracticability::NotPracticable->value => 'Non percorribili',
            CaiTrailPracticability::Unknown->value => 'Sconosciuto',
        ];

        foreach (CaiTrailPracticability::cases() as $case) {
            $chips[] = [
                'key' => $case->key(),
                'label' => $case->getLabel(),
                'shortLabel' => $short[$case->value],
                'count' => count(array_filter($rows, fn (CaiRegionalGroupSicaiStageRow $row): bool => $row->practicability() === $case)),
                'dot' => $case->dot(),
                'active' => $active === $case,
            ];
        }

        return $chips;
    }

    public function isFilteringStages(): bool
    {
        return $this->activePracticability() !== null || trim($this->search) !== '';
    }

    /** "82 tappe" oppure, con un filtro, "3 di 82 tappe". */
    public function stagesCountLabel(int $shown): string
    {
        $total = $this->data()?->stagesCount() ?? 0;

        return $this->isFilteringStages() ? $shown.' di '.$total.' tappe' : $total.' '.($total === 1 ? 'tappa' : 'tappe');
    }

    /** Messaggio dell'elenco vuoto: col solo filtro nomina la percorribilità e la regione, come nel design. */
    public function noStagesMessage(): string
    {
        $practicability = $this->activePracticability();
        $region = $this->region();

        if ($practicability !== null && trim($this->search) === '' && $region !== null) {
            return 'Nessuna tappa «'.mb_strtolower($practicability->getLabel()).'» in '.$region->label().'.';
        }

        return 'Nessuna tappa corrisponde alla ricerca o al filtro.';
    }

    /** "3 punti di appoggio vicini" / "Nessun punto di appoggio vicino" (card di mobile). */
    public static function nearPointsLabel(int $count): string
    {
        return match ($count) {
            0 => 'Nessun punto di appoggio vicino',
            1 => '1 punto di appoggio vicino',
            default => $count.' punti di appoggio vicini',
        };
    }

    /** Scheda del sentiero della tappa, aperta da "Tappe SICAI" (`?da=sicai`). */
    public static function stageUrl(int $trailId): string
    {
        return CaiTrailDetail::getUrl(['trailId' => $trailId, 'da' => CaiTrailDetail::FROM_SICAI]);
    }

    /**
     * Id del sentiero per codice di tappa (i codici sono distinti), per i link delle tappe vicine ai punti di appoggio.
     *
     * @return array<string, int>
     */
    public function stageIdsByCode(): array
    {
        $ids = [];

        foreach ($this->data()->stages ?? [] as $row) {
            $ids[$row->code()] ??= $row->stage->cai_trail_id;
        }

        return $ids;
    }

    // ---- Punti di appoggio ----

    /** Tipo attivo del filtro (testo della fonte), `null` se assente o non presente fra i punti del GR. */
    public function activePointType(): ?string
    {
        foreach (array_keys($this->data()->restPointsByType ?? []) as $type) {
            if (CaiSicaiRestPointType::keyOf($type) === $this->pointType) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Gruppi per tipo (tutti, o solo quello del filtro), ciascuno con i punti per nome.
     *
     * @return list<array{type: CaiSicaiRestPointType, rows: list<CaiRegionalGroupSicaiRestPointRow>}>
     */
    public function pointGroups(): array
    {
        $active = $this->activePointType();
        $groups = [];

        foreach ($this->data()->restPointsByType ?? [] as $type => $rows) {
            if ($active === null || $active === $type) {
                $groups[] = ['type' => CaiSicaiRestPointType::of($type), 'rows' => $rows];
            }
        }

        return $groups;
    }

    /**
     * Pillole del filtro per tipo: "Tutti" e i tipi presenti, con il numero di punti.
     *
     * @return list<array{key: string|null, label: string, count: int, active: bool}>
     */
    public function pointTypeChips(): array
    {
        $data = $this->data();
        $active = $this->activePointType();
        $chips = [['key' => null, 'label' => 'Tutti', 'count' => $data->restPointsCount ?? 0, 'active' => $active === null]];

        foreach ($data->restPointsByType ?? [] as $type => $rows) {
            $style = CaiSicaiRestPointType::of($type);
            $chips[] = ['key' => $style->key(), 'label' => $style->plural(), 'count' => count($rows), 'active' => $active === $type];
        }

        return $chips;
    }

    /** "52 punti di appoggio · 12 nella banca dati rifugi.cai.it". */
    public function pointsCountLabel(): string
    {
        $data = $this->data();
        $total = $data->restPointsCount ?? 0;
        $huts = 0;

        foreach ($data->restPointsByType ?? [] as $rows) {
            $huts += count(array_filter($rows, fn (CaiRegionalGroupSicaiRestPointRow $row): bool => $row->point->cai_hut_id_cai !== null));
        }

        return $total.' '.($total === 1 ? 'punto di appoggio' : 'punti di appoggio').' · '.$huts.' nella banca dati rifugi.cai.it';
    }

    /** Sito senza schema né barra finale ("www.rifugiomondovi.com"). */
    public static function websiteLabel(string $url): string
    {
        return rtrim((string) preg_replace('#^https?://#i', '', trim($url)), '/');
    }

    /** Link del sito: la fonte a volte lo scrive senza schema. */
    public static function websiteHref(string $url): string
    {
        $url = trim($url);

        return preg_match('#^https?://#i', $url) === 1 ? $url : 'https://'.$url;
    }

    public function dashboardUrl(): string
    {
        return RegionalGroupDashboard::getUrl();
    }

    private function matchesSearch(CaiRegionalGroupSicaiStageRow $row): bool
    {
        $query = mb_strtolower(trim($this->search));

        if ($query === '') {
            return true;
        }

        $trail = $row->stage->trail;

        return str_starts_with(mb_strtolower($row->code()), $query)
            || str_contains(mb_strtolower((string) $trail?->from_place), $query)
            || str_contains(mb_strtolower((string) $trail?->to_place), $query);
    }
}
