{{-- Mappa di "Tappe SICAI del GR" (US-1095), widget App\Filament\Widgets\RegionalGroupSicaiStagesMap: sempre resa, anche
     senza regione o senza tappe (lines/markers vuoti), mai dentro un @if. Riquadro informativo in alto a sinistra, "Vista
     regione" sotto lo zoom, legenda (tracciato e tipi di punto) in basso a sinistra su desktop e sotto la mappa su mobile.
     I punti di appoggio si raggruppano sotto lo zoom 12: a scala regionale coprirebbero il tracciato.
     Stili in regional-group-sicai-stages.css (ms-gr-sicai-map__*). --}}
@php
    $legendLines = [
        ['label' => 'Tappe, a colori alternati', 'short' => 'Tappe', 'class' => 'ms-gr-sicai-map__swatch--alternate'],
        ['label' => $practicabilities['difficulty']->getLabel(), 'short' => 'Con difficoltà', 'class' => 'ms-gr-sicai-map__swatch--difficulty'],
        ['label' => $practicabilities['notPracticable']->getLabel(), 'short' => 'Non percorribile', 'class' => 'ms-gr-sicai-map__swatch--not-practicable'],
    ];

    if ($hasVariants) {
        $legendLines[] = ['label' => 'Variante', 'short' => 'Variante', 'class' => 'ms-gr-sicai-map__swatch--variant'];
    }
@endphp
<div class="ms-gr-sicai__card ms-gr-sicai-map">
    <x-cai.map
        :name="$name"
        :lines="$lines"
        :markers="$markers"
        height="var(--ms-gr-sicai-map-height)"
        :fit-max-zoom="12"
        :cluster="true"
        cluster-noun="punti di appoggio"
        attribution="Cartografia CAI · Sentiero Italia CAI · © OpenStreetMap"
    >
        <x-slot:overlay>
            <div class="ms-gr-sicai-map__info">
                {{ $stagesCount }} {{ $stagesCount === 1 ? 'tappa' : 'tappe' }}
                · @if ($pointsCount > 0){{ $pointsCount }} {{ $pointsCount === 1 ? 'punto di appoggio' : 'punti di appoggio' }} · @endif clic su una tappa per l’anteprima
            </div>
            <button type="button" class="ms-gr-sicai-map__reset" title="Vista regione" aria-label="Vista regione" x-on:click="resetView()">
                <svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"></path></svg>
            </button>
            <div class="ms-gr-sicai-map__legend">
                <div class="ms-gr-sicai-map__legend-column">
                    <div class="ms-gr-sicai-map__legend-title">Tracciato</div>
                    @foreach ($legendLines as $entry)
                        <div class="ms-gr-sicai-map__legend-item"><span class="ms-gr-sicai-map__swatch {{ $entry['class'] }}"></span>{{ $entry['label'] }}</div>
                    @endforeach
                </div>
                @if ($pointTypes !== [])
                    <div class="ms-gr-sicai-map__legend-column">
                        <div class="ms-gr-sicai-map__legend-title">Punti di appoggio</div>
                        <div class="ms-gr-sicai-map__legend-types">
                            @foreach ($pointTypes as $type)
                                <div class="ms-gr-sicai-map__legend-item">
                                    <span class="ms-gr-sicai-map__type-icon" style="background: {{ $type->color() }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $type->icon() }}"></path></svg></span>{{ $type->plural() }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </x-slot:overlay>
        <x-slot:footer>
            <div class="ms-gr-sicai-map__footer">
                <div class="ms-gr-sicai-map__footer-row">
                    @foreach ($legendLines as $entry)
                        <div class="ms-gr-sicai-map__legend-item"><span class="ms-gr-sicai-map__swatch {{ $entry['class'] }}"></span>{{ $entry['short'] }}</div>
                    @endforeach
                </div>
                @if ($pointTypes !== [])
                    <div class="ms-gr-sicai-map__footer-row">
                        @foreach ($pointTypes as $type)
                            <div class="ms-gr-sicai-map__legend-item"><span class="ms-gr-sicai-map__type-dot" style="background: {{ $type->color() }}"></span>{{ $type->plural() }}</div>
                        @endforeach
                    </div>
                @endif
            </div>
        </x-slot:footer>
    </x-cai.map>
</div>
