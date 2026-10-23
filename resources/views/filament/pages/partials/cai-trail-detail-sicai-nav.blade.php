{{-- Navigazione fra le tappe SICAI della stessa lettera nella scheda del sentiero (US-1096), design
     tappe-sicai-del-gr.dc.html §"Scheda della tappa": precedente | posizione e varianti | successiva su desktop; su mobile
     la posizione sopra, precedente e successiva affiancate, varianti sotto (aree della griglia in cai-trail-detail.css).
     `navigation` = CaiSicaiStageNavigation. Su mobile precedente mostra la partenza, successiva l'arrivo. --}}
@php
    $steps = [
        'prev' => ['row' => $navigation->previous, 'label' => 'Tappa precedente', 'short' => '← Precedente', 'none' => 'Prima tappa'],
        'next' => ['row' => $navigation->next, 'label' => 'Tappa successiva', 'short' => 'Successiva →', 'none' => 'Ultima tappa'],
    ];
@endphp
<nav class="ms-trail-sicai__nav" aria-label="Tappe del Sentiero Italia CAI">
    @foreach ($steps as $side => $step)
        @php
            $row = $step['row'];
            $arrow = $side === 'prev' ? '←' : '→';
        @endphp
        @if ($row !== null)
            @php
                $place = $side === 'prev' ? $row->stage->trail?->from_place : $row->stage->trail?->to_place;
            @endphp
            <a href="{{ $this->sicaiStageUrl($row) }}" class="ms-trail-sicai__step ms-trail-sicai__step--{{ $side }}" rel="{{ $side }}" title="{{ $row->code() }} · {{ $row->route() }}">
                <span class="ms-trail-sicai__arrow ms-trail__desktop-inline" aria-hidden="true">{{ $arrow }}</span>
                <span class="ms-trail-sicai__step-body">
                    <span class="ms-trail-sicai__step-label"><span class="ms-trail__desktop-inline">{{ $step['label'] }}</span><span class="ms-trail__mobile-inline">{{ $step['short'] }}</span></span>
                    <span class="ms-trail-sicai__step-code">
                        <span class="ms-trail-sicai__dot" style="background: {{ $row->practicability()->dot() }}" aria-hidden="true"></span>
                        <span class="ms-trail-sicai__step-text">{{ $row->code() }}<span class="ms-trail__desktop-inline"> · {{ $row->route() }}</span></span>
                    </span>
                    <span class="ms-trail-sicai__step-place ms-trail__mobile-inline">{{ filled($place) ? $place : '—' }}</span>
                </span>
            </a>
        @else
            <div class="ms-trail-sicai__step ms-trail-sicai__step--{{ $side }} ms-trail-sicai__step--none">
                <span class="ms-trail-sicai__arrow ms-trail__desktop-inline" aria-hidden="true">{{ $arrow }}</span>
                <span class="ms-trail-sicai__step-body">
                    <span class="ms-trail-sicai__step-label"><span class="ms-trail__desktop-inline">{{ $step['label'] }}</span><span class="ms-trail__mobile-inline">{{ $step['short'] }}</span></span>
                    <span class="ms-trail-sicai__step-none"><span class="ms-trail__desktop-inline">{{ $this->sicaiNoNeighbourLabel($navigation, $side === 'prev') }}</span><span class="ms-trail__mobile-inline">{{ $step['none'] }}</span></span>
                </span>
            </div>
        @endif
    @endforeach

    <p class="ms-trail-sicai__where">{{ $this->sicaiPositionLabel($navigation) }}</p>

    @if ($navigation->variants !== [])
        <div class="ms-trail-sicai__extra">
            Varianti
            @foreach ($navigation->variants as $variant)
                <a href="{{ $this->sicaiStageUrl($variant) }}" class="ms-trail-sicai__variant">{{ $variant->code() }}</a>
            @endforeach
        </div>
    @elseif ($navigation->current->isVariant)
        <a href="{{ $this->sicaiStageUrl($navigation->main) }}" class="ms-trail-sicai__extra ms-trail-sicai__back">Torna alla tappa {{ $navigation->main->code() }}</a>
    @endif
</nav>
