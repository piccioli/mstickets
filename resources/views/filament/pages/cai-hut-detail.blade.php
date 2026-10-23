{{-- Scheda del rifugio (Fase 10.4, US-1035), design docs/project/design/fase-10/rifugi-di-sezione.dc.html §2. Stili in
     resources/css/filament/admin/cai-hut-detail.css. Dati già formattati da CaiHutSheet: `null` = "—". La sezione
     "Gestione" è resa solo se `canViewManagement()`: senza permesso non c'è nulla nel markup, nemmeno nascosto. --}}
@use('App\Domain\CaiDirectory\Support\CaiHutSheet')
@use('App\Filament\Pages\CaiHutDetail')
@php
    $hut = $this->hut;
    $sheet = $this->sheet();
    $photo = $sheet->photo();
    $back = $this->backLink();
    $statusLabel = $sheet->statusLabel();
    $altitude = $sheet->altitude();
    $coordinates = $sheet->coordinates();
    $lastUpdate = $sheet->lastUpdate();
    $openingTimes = $sheet->openingTimes();
    $showManagement = $this->canViewManagement();
    $today = $this->today();
    $contractStatus = $showManagement ? $sheet->contractStatus($today) : null;
    $contractNote = $showManagement ? $sheet->contractNote($today) : null;
    $alert = $contractStatus?->isAlert() === true;
@endphp
<x-filament-panels::page>
    <div class="ms-hut">
        <nav class="ms-hut__crumbs" aria-label="Percorso">
            <a href="{{ $this->dashboardUrl() }}" class="ms-hut__crumb ms-hut__crumb--desktop">← Dashboard</a>
            @if ($back !== null)
                <span class="ms-hut__crumb-sep ms-hut__crumb--desktop" aria-hidden="true">/</span>
                <a href="{{ $back['url'] }}" class="ms-hut__crumb ms-hut__crumb--desktop">{{ $back['label'] }}</a>
                <a href="{{ $back['url'] }}" class="ms-hut__crumb ms-hut__crumb--mobile">← {{ $back['label'] }}</a>
            @endif
            <span class="ms-hut__crumb-sep ms-hut__crumb--desktop" aria-hidden="true">/</span>
            <span class="ms-hut__crumb-current ms-hut__crumb--desktop">{{ $hut->name }}</span>
        </nav>

        {{-- Testata --}}
        <header @class(['ms-hut__hero', 'ms-hut__hero--photo' => $photo !== null, 'ms-hut__hero--nophoto' => $photo === null])>
            @if ($photo !== null)
                <img src="{{ $photo }}" alt="" class="ms-hut__hero-img"
                    onerror="this.closest('.ms-hut__hero').classList.replace('ms-hut__hero--photo', 'ms-hut__hero--nophoto'); this.remove()">
            @endif
            <div class="ms-hut__hero-placeholder">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 19l6-9 4 5 3-3 5 7z"></path></svg>
                <span class="ms-hut__hero-placeholder-long">Nessuna foto disponibile su rifugi.cai.it</span>
                <span class="ms-hut__hero-placeholder-short">Nessuna foto disponibile</span>
            </div>
            <div class="ms-hut__hero-content">
                <div class="ms-hut__hero-main">
                    <div class="ms-hut__pills">
                        <span class="ms-hut__type-pill">
                            <span class="ms-hut__dot" style="background: {{ $hut->type?->color() ?? '#5b6475' }}"></span>
                            {{ $hut->type?->getLabel() ?? 'Tipo non indicato' }}
                        </span>
                        @if ($statusLabel !== null)
                            <span class="ms-hut__status-pill"
                                @if ($hut->status !== null) style="background: {{ $hut->status->background() }}; color: {{ $hut->status->foreground() }}" @endif
                            >{{ $statusLabel }}</span>
                        @endif
                    </div>
                    <h1 class="ms-hut__name">{{ $hut->name }}</h1>
                    <p class="ms-hut__alias">
                        {{ $hut->alias ?? '—' }}<span class="ms-hut__alias-altitude"> · {{ $altitude ?? '—' }} m</span>
                    </p>
                </div>
                <div class="ms-hut__altitude">
                    <span class="ms-hut__altitude-label">Quota</span>
                    <span class="ms-hut__altitude-value">{{ $altitude === null ? '—' : $altitude.' m' }}</span>
                </div>
            </div>
        </header>

        <div class="ms-hut__grid">
            <div class="ms-hut__main">
                <section class="ms-hut__card" aria-labelledby="hut-where">
                    <h2 id="hut-where" class="ms-hut__card-title">Dove si trova</h2>
                    <dl class="ms-hut__pairs ms-hut__pairs--two">
                        @foreach ($sheet->location() as $row)
                            <div class="ms-hut__pair">
                                <dt>{{ $row['label'] }}</dt>
                                <dd @class(['ms-hut__missing' => $row['value'] === null])>{{ $row['value'] ?? '—' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>

                <section class="ms-hut__card" aria-labelledby="hut-services">
                    <h2 id="hut-services" class="ms-hut__card-title">Ospitalità e servizi</h2>
                    <div class="ms-hut__beds">
                        <div class="ms-hut__bed-box">
                            <span class="ms-hut__bed-label">Posti letto totali</span>
                            <span class="ms-hut__bed-value">{{ $sheet->beds() ?? '—' }}</span>
                        </div>
                        <div class="ms-hut__bed-box">
                            <span class="ms-hut__bed-label">Posti invernali</span>
                            <span class="ms-hut__bed-value">{{ $sheet->winterBeds() ?? '—' }}</span>
                        </div>
                    </div>
                    <ul class="ms-hut__services">
                        @foreach ($sheet->services() as $service)
                            <li @class([
                                'ms-hut__service',
                                'ms-hut__service--yes' => $service['value'] === 1,
                                'ms-hut__service--no' => $service['value'] === 0,
                                'ms-hut__service--unknown' => $service['value'] === null,
                            ]) data-service="{{ $service['label'] }}">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $service['icon'] }}"></path></svg>
                                <span class="ms-hut__service-label">{{ $service['label'] }}</span>
                                <span class="ms-hut__service-value">{{ CaiHutSheet::flagLabel($service['value']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>

                <section class="ms-hut__card" aria-labelledby="hut-contacts">
                    <h2 id="hut-contacts" class="ms-hut__card-title">Contatti</h2>
                    <dl class="ms-hut__pairs ms-hut__pairs--two">
                        @foreach ($sheet->contacts() as $contact)
                            <div class="ms-hut__pair">
                                <dt>{{ $contact['label'] }}</dt>
                                @if ($contact['href'] !== null)
                                    <dd><a href="{{ $contact['href'] }}" @if (str_starts_with($contact['href'], 'http')) target="_blank" rel="noopener" @endif>{{ $contact['value'] }}</a></dd>
                                @else
                                    <dd class="ms-hut__missing">—</dd>
                                @endif
                            </div>
                        @endforeach
                    </dl>
                    <div class="ms-hut__pair ms-hut__pair--opening">
                        <span class="ms-hut__pair-label">Orari di apertura</span>
                        <span @class(['ms-hut__pair-value', 'ms-hut__missing' => $openingTimes === null])>{{ $openingTimes ?? '—' }}</span>
                    </div>
                </section>
            </div>

            <aside class="ms-hut__side">
                <div class="ms-hut__card ms-hut__card--map">
                    <x-cai.map :name="CaiHutDetail::MAP_NAME" :markers="$this->markers()" height="var(--ms-hut-map-height)" />
                    <div class="ms-hut__coordinates">
                        <span>{{ $coordinates ?? 'Coordinate non disponibili' }}</span>
                        <span class="ms-hut__coordinates-datum">WGS84</span>
                    </div>
                </div>

                <div class="ms-hut__card ms-hut__card--source">
                    <span class="ms-hut__eyebrow">Fonte</span>
                    <a href="{{ $sheet->sourceUrl() }}" target="_blank" rel="noopener" class="ms-hut__source-link">Vedi su rifugi.cai.it ↗</a>
                    <span class="ms-hut__updated">Ultimo aggiornamento del dato: <strong>{{ $lastUpdate ?? '—' }}</strong></span>
                </div>
            </aside>
        </div>

        @if ($showManagement)
            <section class="ms-hut__management" aria-labelledby="hut-management">
                <div class="ms-hut__management-head">
                    <div class="ms-hut__management-title">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 11h12v9H6zM8.5 11V8a3.5 3.5 0 0 1 7 0v3"></path></svg>
                        <h2 id="hut-management">Gestione</h2>
                        <span class="ms-hut__reserved">Riservato alla Sezione</span>
                    </div>
                    @if ($alert)
                        <span @class(['ms-hut__alert-pill', 'ms-hut__alert--'.$contractStatus->value])>
                            {{ $contractStatus->pill() }}<span class="ms-hut__alert-pill-date"> · {{ $sheet->contractEnd() }}</span>
                        </span>
                    @endif
                </div>
                <p class="ms-hut__management-note">Informazioni visibili solo agli amministratori della Sezione, non mostrate nelle pagine pubbliche.</p>

                <div class="ms-hut__management-grid">
                    <div class="ms-hut__card ms-hut__card--compact">
                        <h3 class="ms-hut__subcard-title">Gestore</h3>
                        @if ($sheet->hasManager())
                            <dl class="ms-hut__pairs ms-hut__pairs--compact">
                                @foreach ($sheet->manager() as $row)
                                    <div class="ms-hut__pair">
                                        <dt>{{ $row['label'] }}</dt>
                                        <dd @class(['ms-hut__missing' => $row['value'] === null])>{{ $row['value'] ?? '—' }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        @else
                            <p class="ms-hut__empty-note">Nessun gestore registrato nella banca dati.</p>
                        @endif
                    </div>

                    <div class="ms-hut__card ms-hut__card--compact">
                        <h3 class="ms-hut__subcard-title">Contratto</h3>
                        @php
                            $possession = $sheet->possessionType();
                            $start = $sheet->contractStart();
                            $end = $sheet->contractEnd();
                            $fee = $sheet->fee();
                        @endphp
                        <dl class="ms-hut__pairs ms-hut__pairs--compact">
                            <div class="ms-hut__pair">
                                <dt>Tipo di possesso</dt>
                                <dd @class(['ms-hut__missing' => $possession === null])>{{ $possession ?? '—' }}</dd>
                            </div>
                            <div class="ms-hut__pair">
                                <dt>Data di inizio</dt>
                                <dd @class(['ms-hut__missing' => $start === null])>{{ $start ?? '—' }}</dd>
                            </div>
                            <div @class(['ms-hut__pair', 'ms-hut__pair--expiry', 'ms-hut__alert--'.$contractStatus?->value => $alert])>
                                <dt>Data di scadenza</dt>
                                <dd @class(['ms-hut__missing' => $end === null])>
                                    <span class="ms-hut__expiry-date">{{ $end ?? '—' }}</span>
                                    @if ($contractNote !== null)
                                        <span class="ms-hut__expiry-note">{{ $contractNote }}</span>
                                    @endif
                                </dd>
                            </div>
                            <div class="ms-hut__pair">
                                <dt>Canone annuo</dt>
                                <dd @class(['ms-hut__missing' => $fee === null])>{{ $fee ?? '—' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="ms-hut__card ms-hut__card--compact">
                        <h3 class="ms-hut__subcard-title">Proprietà</h3>
                        <dl class="ms-hut__pairs ms-hut__pairs--compact">
                            @foreach ($sheet->property() as $row)
                                <div class="ms-hut__pair">
                                    <dt>{{ $row['label'] }}</dt>
                                    <dd @class(['ms-hut__missing' => $row['value'] === null])>{{ $row['value'] ?? '—' }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                </div>

                <div class="ms-hut__card ms-hut__card--compact">
                    <h3 class="ms-hut__subcard-title">Certificazioni e impianti</h3>
                    <ul class="ms-hut__certifications">
                        @foreach ($sheet->certifications() as $certification)
                            <li @class([
                                'ms-hut__certification',
                                'ms-hut__service--yes' => $certification['value'] === 1,
                                'ms-hut__service--no' => $certification['value'] === 0,
                                'ms-hut__service--unknown' => $certification['value'] === null,
                            ])>
                                <span class="ms-hut__service-label">{{ $certification['label'] }}</span>
                                <span class="ms-hut__service-value">{{ CaiHutSheet::flagLabel($certification['value']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>
        @endif
    </div>
</x-filament-panels::page>
