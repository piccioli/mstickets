<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\CaiDirectory\Models\CaiSection;
use App\Domain\CaiDirectory\Models\CaiTrail;
use App\Domain\CaiDirectory\Policies\CaiTrailPolicy;
use App\Domain\CaiDirectory\Support\CaiMapLine;
use App\Domain\CaiDirectory\Support\CaiMapMarker;
use App\Domain\Identity\Enums\CustomerType;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\UserRole;
use App\Domain\Identity\Models\User;
use App\Filament\Resources\CaiSections\CaiSectionResource;
use App\Support\Formatting\ItalianNumberFormatter;
use Filament\Pages\Page;
use Filament\Panel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;

/**
 * Scheda di un sentiero osm2cai (Fase 10.6, US-1055), design `docs/project/design/fase-10/sentieri-di-sezione.dc.html`
 * §2: testata (segnavia, nome, partenza → arrivo, pillole di accatastamento e percorribilità, link a osm2cai e
 * OpenStreetMap), avviso se non percorribile o percorribile con difficoltà, mappa del tracciato con partenza (A) e
 * arrivo (B), dati del sentiero e Sezioni che lo gestiscono.
 *
 * Due livelli di autorizzazione, come la scheda del rifugio: {@see canAccess()} ammette staff con `CaiDirectoryView` e
 * customer Sezione o GR, {@see mount()} verifica {@see CaiTrailPolicy::view()} sul singolo sentiero (una delle sue
 * Sezioni, GR per prefisso, staff): un id manipolato nell'URL dà 403. Fuori dalla navigazione: si apre dall'elenco;
 * `?da=gr` ("Sentieri del GR", 10.7) e `?da=sezione-gr&codice=…` (Sezione vista dal GR, {@see RegionalGroupSectionTrails})
 * cambiano il link di ritorno, che riporta i parametri dell'elenco (filtri, ricerca, ordinamento, pagina).
 */
class CaiTrailDetail extends Page
{
    /** Nome della mappa negli eventi `cai-map-*` (vedi `x-cai.map`). */
    public const MAP_NAME = 'trail';

    /** `?da=` della scheda aperta da "Sentieri del GR". */
    public const FROM_GROUP = 'gr';

    /** `?da=` della scheda aperta dai sentieri di una Sezione visti dal GR (con `?codice=`). */
    public const FROM_GROUP_SECTION = 'sezione-gr';

    /** Inquadratura massima del tracciato (il design apre a zoom 15). */
    public const MAP_FIT_MAX_ZOOM = 15;

    /** Spessore del tracciato: quello di una linea selezionata nel design (5 px su bordo bianco di 10). */
    private const LINE_WEIGHT = 5;

    /** Colori dei marker di partenza e arrivo: `--ms-brand` e `--ms-text-strong` del tema. */
    private const START_COLOR = '#17a180';

    private const END_COLOR = '#3c4557';

    protected string $view = 'filament.pages.cai-trail-detail';

    protected static ?string $slug = 'sentieri';

    protected static bool $shouldRegisterNavigation = false;

    public CaiTrail $trail;

    /** Pagina di provenienza: {@see FROM_GROUP}, {@see FROM_GROUP_SECTION}. */
    #[Url(as: 'da')]
    public ?string $from = null;

    /** Codice CAI della Sezione con {@see FROM_GROUP_SECTION}. */
    #[Url(as: 'codice')]
    public ?string $sectionCode = null;

    /**
     * Parametri dell'elenco ({@see SectionTrails::LIST_QUERY_KEYS}, o {@see RegionalGroupTrails::LIST_QUERY_KEYS} con
     * `?da=gr`) con cui si è aperta la scheda: il link di ritorno li riporta, così filtri e ordinamento restano quelli di prima.
     *
     * @var array<string, string|list<string>>
     */
    public array $listQuery = [];

    public static function getRoutePath(Panel $panel): string
    {
        return parent::getRoutePath($panel).'/{trailId}';
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

    public function mount(string $trailId): void
    {
        abort_unless(ctype_digit($trailId) && strlen($trailId) <= 18, 404);

        $trail = CaiTrail::query()->with('sections:codice_cai,name')->findOrFail((int) $trailId);

        abort_unless(Auth::user()?->can('view', $trail) === true, 403);

        $this->trail = $trail;
        $this->listQuery = self::listQueryFrom(
            request()->query(),
            request()->query('da') === self::FROM_GROUP ? RegionalGroupTrails::LIST_QUERY_KEYS : SectionTrails::LIST_QUERY_KEYS,
        );
    }

    /**
     * Solo le chiavi dell'elenco: stringhe non vuote, e per `stato` una lista di stringhe. Il resto è scartato; i valori
     * non validi li normalizza poi l'elenco, come per un URL scritto a mano.
     *
     * @param  array<array-key, mixed>  $query
     * @param  list<string>  $keys
     * @return array<string, string|list<string>>
     */
    public static function listQueryFrom(array $query, array $keys = SectionTrails::LIST_QUERY_KEYS): array
    {
        $list = [];

        foreach ($keys as $key) {
            $value = $query[$key] ?? null;

            if (is_string($value) && $value !== '' && strlen($value) <= 100) {
                $list[$key] = $value;
            } elseif (is_array($value)) {
                $values = array_values(array_filter($value, fn (mixed $item): bool => is_string($item) && $item !== '' && strlen($item) <= 100));

                if ($values !== [] && count($values) <= 10) {
                    $list[$key] = $values;
                }
            }
        }

        return $list;
    }

    public function getTitle(): string
    {
        return filled($this->trail->name) ? (string) $this->trail->name : 'Sentiero '.$this->number();
    }

    /** L'intestazione è resa dalla vista (testata col segnavia), non dall'header standard di Filament. */
    public function getHeading(): string
    {
        return '';
    }

    /** Numero del sentiero (`ref`), "—" se assente. */
    public function number(): string
    {
        return filled($this->trail->ref) ? (string) $this->trail->ref : '—';
    }

    public function name(): ?string
    {
        return filled($this->trail->name) ? (string) $this->trail->name : null;
    }

    public function startPlace(): ?string
    {
        return filled($this->trail->from_place) ? (string) $this->trail->from_place : null;
    }

    public function endPlace(): ?string
    {
        return filled($this->trail->to_place) ? (string) $this->trail->to_place : null;
    }

    public function route(): string
    {
        return SectionTrails::route($this->trail);
    }

    /** Data dell'ultimo aggiornamento della percorribilità (gg/mm/aaaa), `null` se manca. */
    public function practicabilityDate(): ?string
    {
        return SectionTrails::practicabilityDate($this->trail);
    }

    /**
     * Riquadri in testa ai dati: lunghezza, difficoltà CAI, dislivelli; `null` = "—".
     *
     * @return list<array{label: string, value: string|null}>
     */
    public function figures(): array
    {
        $trail = $this->trail;

        return [
            ['label' => 'Lunghezza', 'value' => $trail->length_km === null ? null : ItalianNumberFormatter::kilometers((float) $trail->length_km)],
            ['label' => 'Difficoltà CAI', 'value' => filled($trail->cai_scale) ? (string) $trail->cai_scale : null],
            ['label' => 'Dislivello in salita', 'value' => $trail->ascent === null ? null : '+'.ItalianNumberFormatter::integer($trail->ascent).' m'],
            ['label' => 'Dislivello in discesa', 'value' => $trail->descent === null ? null : '−'.ItalianNumberFormatter::integer($trail->descent).' m'],
        ];
    }

    /**
     * Righe "Dati del sentiero"; `mobile` = solo nel layout mobile (partenza e arrivo, che su desktop stanno sotto la
     * mappa). `null` = "—".
     *
     * @return list<array{label: string, value: string|null, mobile: bool}>
     */
    public function details(): array
    {
        return [
            ['label' => 'Partenza', 'value' => $this->startPlace(), 'mobile' => true],
            ['label' => 'Arrivo', 'value' => $this->endPlace(), 'mobile' => true],
            ['label' => 'Stato di accatastamento', 'value' => $this->trail->status->getLabel(), 'mobile' => false],
            ['label' => 'Data di validazione', 'value' => $this->trail->validation_date?->format('d/m/Y'), 'mobile' => false],
            ['label' => 'Percorribilità aggiornata il', 'value' => $this->practicabilityDate(), 'mobile' => false],
        ];
    }

    /**
     * Sezioni che gestiscono il sentiero, per nome; `current` = la Sezione dell'utente ("Questa Sezione").
     *
     * @return Collection<int, array{name: string, current: bool}>
     */
    public function sections(): Collection
    {
        $user = Auth::user();
        $own = $user instanceof User
            ? CaiSection::query()->where('user_id', $user->id)->pluck('codice_cai')->all()
            : [];

        return $this->trail->sections
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->map(fn (CaiSection $section): array => [
                'name' => (string) $section->name,
                'current' => in_array($section->codice_cai, $own, true),
            ]);
    }

    public function osm2caiUrl(): string
    {
        return 'https://osm2cai.cai.it/hiking-route/id/'.$this->trail->id;
    }

    /** Relazione OpenStreetMap del sentiero, `null` se osm2cai non ne riporta l'id. */
    public function openStreetMapUrl(): ?string
    {
        return $this->trail->osmid === null ? null : 'https://www.openstreetmap.org/relation/'.$this->trail->osmid;
    }

    /** @return list<CaiMapLine> */
    public function lines(): array
    {
        $line = CaiMapLine::forTrail($this->trail);

        return $line === null ? [] : [new CaiMapLine(
            id: $line->id,
            geojson: $line->geojson,
            color: $line->color,
            label: $line->label,
            weight: self::LINE_WEIGHT,
        )];
    }

    /**
     * Marker di partenza (A) e arrivo (B): primo e ultimo punto del tracciato.
     *
     * @return list<CaiMapMarker>
     */
    public function markers(): array
    {
        $points = self::endpoints($this->trail->geometry);

        if ($points === null) {
            return [];
        }

        [$start, $end] = $points;

        return [
            new CaiMapMarker(id: 'start', lat: $start[1], lng: $start[0], label: 'Partenza: '.($this->startPlace() ?? '—'), color: self::START_COLOR, letter: 'A'),
            new CaiMapMarker(id: 'end', lat: $end[1], lng: $end[0], label: 'Arrivo: '.($this->endPlace() ?? '—'), color: self::END_COLOR, letter: 'B'),
        ];
    }

    /**
     * Primo e ultimo punto `[lng, lat]` di un GeoJSON `LineString`/`MultiLineString` (anche dentro una `Feature`),
     * `null` se la geometria manca o non è riconosciuta.
     *
     * @param  array<string, mixed>|null  $geometry
     * @return array{0: array{0: float, 1: float}, 1: array{0: float, 1: float}}|null
     */
    public static function endpoints(?array $geometry): ?array
    {
        if (($geometry['type'] ?? null) === 'Feature') {
            $geometry = is_array($geometry['geometry'] ?? null) ? $geometry['geometry'] : null;
        }

        $coordinates = $geometry['coordinates'] ?? null;

        if (! is_array($coordinates)) {
            return null;
        }

        $points = match ($geometry['type'] ?? null) {
            'LineString' => $coordinates,
            'MultiLineString' => array_merge(...array_values(array_filter($coordinates, 'is_array'))),
            default => [],
        };
        $points = array_values(array_filter($points, fn (mixed $point): bool => is_array($point)
            && is_numeric($point[0] ?? null) && is_numeric($point[1] ?? null)));

        if ($points === []) {
            return null;
        }

        $first = $points[0];
        $last = $points[count($points) - 1];

        return [[(float) $first[0], (float) $first[1]], [(float) $last[0], (float) $last[1]]];
    }

    /**
     * Elenco da cui si arriva: i sentieri di una Sezione visti dal GR (`?da=sezione-gr&codice=…`, utente GR), "Sentieri
     * del GR" (`?da=gr` o utente GR), "Sentieri di Sezione" (utente Sezione), per lo staff la scheda della prima Sezione
     * del sentiero (se esiste). I parametri dell'elenco tornano nel link.
     *
     * @return array{label: string, url: string}|null
     */
    public function backLink(): ?array
    {
        $user = Auth::user();
        $customerType = $user instanceof User && $user->hasRole(UserRole::Customer->value) ? $user->customer_type : null;

        if ($this->from === self::FROM_GROUP_SECTION && $customerType === CustomerType::GruppoRegionale
            && ctype_digit((string) $this->sectionCode) && strlen((string) $this->sectionCode) <= 20) {
            return ['label' => 'Sentieri di Sezione', 'url' => RegionalGroupSectionTrails::getUrl(['codice' => $this->sectionCode, ...$this->listQuery])];
        }

        if ($this->from === self::FROM_GROUP || $customerType === CustomerType::GruppoRegionale) {
            return ['label' => 'Sentieri del GR', 'url' => RegionalGroupTrails::getUrl($this->from === self::FROM_GROUP ? $this->listQuery : [])];
        }

        if ($customerType === CustomerType::Sezione) {
            return ['label' => 'Sentieri di Sezione', 'url' => SectionTrails::getUrl($this->listQuery)];
        }

        $section = $this->trail->sections->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->first();

        return $section === null ? null : [
            'label' => (string) $section->name,
            'url' => CaiSectionResource::getUrl('view', ['record' => $section->codice_cai]),
        ];
    }

    public function dashboardUrl(): string
    {
        return Dashboard::getUrl();
    }
}
