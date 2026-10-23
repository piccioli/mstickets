{{-- Scheda del sentiero (Fase 10.6, US-1055), design docs/project/design/fase-10/sentieri-di-sezione.dc.html §2. Stili in
     resources/css/filament/admin/cai-trail-detail.css (segnavia e pillola dello stato in cai-trails.css). Desktop e
     mobile hanno disposizioni diverse: i riquadri dei dati e i link alle fonti sono resi due volte (`--desktop` /
     `--mobile`) e il CSS mostra la variante giusta; partenza e arrivo stanno sotto la mappa su desktop, fra i dati su
     mobile. Valori mancanti: "—". --}}
@use('App\Filament\Pages\CaiTrailDetail')
@php
    $trail = $this->trail;
    $back = $this->backLink();
    $name = $this->name();
    $start = $this->startPlace();
    $end = $this->endPlace();
    $updated = $this->practicabilityDate();
    $practicability = $trail->practicability;
    $alert = $practicability->alertTitle();
    $figures = $this->figures();
    $sections = $this->sections();
    $osmUrl = $this->openStreetMapUrl();
    $pillStyle = 'background: '.$practicability->background().'; color: '.$practicability->foreground();
@endphp
<x-filament-panels::page>
    <div class="ms-trail">
        <nav class="ms-trail__crumbs" aria-label="Percorso">
            <a href="{{ $this->dashboardUrl() }}" class="ms-trail__crumb ms-trail__desktop">← Dashboard</a>
            @if ($back !== null)
                <span class="ms-trail__crumb-sep ms-trail__desktop" aria-hidden="true">/</span>
                <a href="{{ $back['url'] }}" class="ms-trail__crumb ms-trail__desktop">{{ $back['label'] }}</a>
                <a href="{{ $back['url'] }}" class="ms-trail__crumb ms-trail__mobile">← {{ $back['label'] }}</a>
            @endif
            <span class="ms-trail__crumb-sep ms-trail__desktop" aria-hidden="true">/</span>
            <span class="ms-trail__crumb-current ms-trail__desktop">Sentiero {{ $this->number() }}</span>
        </nav>

        {{-- Testata --}}
        <header class="ms-trail__hero">
            <div class="ms-trail__hero-title">
                <x-cai.trail-sign :ref="$trail->ref" size="xl" />
                <h1 @class(['ms-trail__name', 'ms-trail__missing' => $name === null])>{{ $name ?? 'Sentiero senza nome' }}</h1>
            </div>
            <div class="ms-trail__hero-main">
                <p class="ms-trail__route">{{ $this->route() }}</p>
                <div class="ms-trail__pills">
                    <span class="ms-cai-trail-status ms-trail__status" style="--ms-cai-trail-status-color: {{ $trail->status->color() }}"><span class="ms-trail__desktop-inline">Accatastamento:&nbsp;</span>{{ $trail->status->getLabel() }}</span>
                    <span class="ms-trail__practicability" style="{{ $pillStyle }}">{{ $practicability->getLabel() }}</span>
                    <span class="ms-trail__updated ms-trail__desktop-inline">aggiornato il <strong @class(['ms-trail__missing' => $updated === null])>{{ $updated ?? '—' }}</strong></span>
                </div>
            </div>
            <div class="ms-trail__sources ms-trail__desktop">
                <a href="{{ $this->osm2caiUrl() }}" target="_blank" rel="noopener" class="ms-trail__source ms-trail__source--primary">Vedi su osm2cai ↗</a>
                @if ($osmUrl !== null)
                    <a href="{{ $osmUrl }}" target="_blank" rel="noopener" class="ms-trail__source">Vedi su OpenStreetMap ↗</a>
                @endif
            </div>
        </header>

        @if ($alert !== null)
            <div class="ms-trail__alert" role="status" style="{{ $pillStyle }}; border-color: {{ $practicability->alertBorder() }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l10 18H2zM12 10v5M12 18v.01"></path></svg>
                <div class="ms-trail__alert-text">
                    <strong class="ms-trail__alert-title">{{ $alert }}</strong>
                    <span class="ms-trail__desktop-inline">Stato di percorribilità aggiornato il {{ $updated ?? '—' }} · fonte osm2cai</span>
                    <span class="ms-trail__mobile-inline">Aggiornato il {{ $updated ?? '—' }} · fonte osm2cai</span>
                </div>
            </div>
        @endif

        <div class="ms-trail__grid">
            <div class="ms-trail__card ms-trail__card--map">
                <x-cai.map
                    :name="CaiTrailDetail::MAP_NAME"
                    :lines="$this->lines()"
                    :markers="$this->markers()"
                    :selected="$trail->id"
                    :fit-max-zoom="CaiTrailDetail::MAP_FIT_MAX_ZOOM"
                    height="var(--ms-trail-map-height)"
                />
                <div class="ms-trail__ends ms-trail__desktop">
                    <span class="ms-trail__end">
                        <span class="ms-trail__end-key ms-trail__end-key--start" aria-hidden="true">A</span>
                        <span class="ms-trail__end-label">Partenza</span>
                        <strong @class(['ms-trail__missing' => $start === null])>{{ $start ?? '—' }}</strong>
                    </span>
                    <span class="ms-trail__end">
                        <span class="ms-trail__end-key ms-trail__end-key--end" aria-hidden="true">B</span>
                        <span class="ms-trail__end-label">Arrivo</span>
                        <strong @class(['ms-trail__missing' => $end === null])>{{ $end ?? '—' }}</strong>
                    </span>
                </div>
            </div>

            <ul class="ms-trail__figures ms-trail__figures--mobile ms-trail__mobile">
                @foreach ($figures as $figure)
                    <li class="ms-trail__figure">
                        <span class="ms-trail__figure-label">{{ $figure['label'] }}</span>
                        <span @class(['ms-trail__figure-value', 'ms-trail__missing' => $figure['value'] === null])>{{ $figure['value'] ?? '—' }}</span>
                    </li>
                @endforeach
            </ul>

            <div class="ms-trail__side">
                <section class="ms-trail__card" aria-labelledby="trail-data">
                    <h2 id="trail-data" class="ms-trail__card-title">Dati del sentiero</h2>
                    <ul class="ms-trail__figures ms-trail__desktop">
                        @foreach ($figures as $figure)
                            <li class="ms-trail__figure">
                                <span class="ms-trail__figure-label">{{ $figure['label'] }}</span>
                                <span @class(['ms-trail__figure-value', 'ms-trail__missing' => $figure['value'] === null])>{{ $figure['value'] ?? '—' }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <dl class="ms-trail__pairs">
                        @foreach ($this->details() as $row)
                            <div @class(['ms-trail__pair', 'ms-trail__mobile' => $row['mobile']])>
                                <dt>{{ $row['label'] }}</dt>
                                <dd @class(['ms-trail__missing' => $row['value'] === null])>{{ $row['value'] ?? '—' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>

                <section class="ms-trail__card" aria-labelledby="trail-sections">
                    <div class="ms-trail__card-head">
                        <h2 id="trail-sections" class="ms-trail__card-title">Sezioni che lo gestiscono</h2>
                        <span class="ms-trail__count">{{ $sections->count() }}</span>
                    </div>
                    @if ($sections->count() > 1)
                        <p class="ms-trail__shared">Sentiero gestito in comune da {{ $sections->count() }} Sezioni</p>
                    @endif
                    <ul class="ms-trail__sections">
                        @forelse ($sections as $section)
                            <li class="ms-trail__section">
                                <span class="ms-trail__section-name">{{ $section['name'] }}</span>
                                @if ($section['current'])
                                    <span class="ms-trail__own">Questa Sezione</span>
                                @endif
                            </li>
                        @empty
                            <li class="ms-trail__section ms-trail__missing">Nessuna Sezione in anagrafica</li>
                        @endforelse
                    </ul>
                </section>
            </div>
        </div>

        <div class="ms-trail__sources ms-trail__sources--mobile ms-trail__mobile">
            <a href="{{ $this->osm2caiUrl() }}" target="_blank" rel="noopener" class="ms-trail__source ms-trail__source--primary">Vedi su osm2cai ↗</a>
            @if ($osmUrl !== null)
                <a href="{{ $osmUrl }}" target="_blank" rel="noopener" class="ms-trail__source">Vedi su OpenStreetMap ↗</a>
            @endif
        </div>
    </div>
</x-filament-panels::page>
