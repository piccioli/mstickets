{{-- Valore di una Sezione (o del Totale) nel bilancio consolidato del GR (Fase 10.3, US-1024): "—" se mancante,
     con segno e in rosso se negativo per il risultato netto (`signed`), su fondo ambra con "valore da verificare"
     se segnalato (`flagged`). Stili in resources/css/filament/admin/regional-group-financial-statements.css. --}}
@use('App\Support\Formatting\ItalianNumberFormatter')
@props([
    'value' => null,
    'signed' => false,
    'flagged' => false,
])
@if ($value === null)
    <span class="ms-consolidated__missing">—</span>
@else
    @php
        $formatted = $signed ? ItalianNumberFormatter::signedEuro($value) : ItalianNumberFormatter::euro($value);
    @endphp
    @if ($flagged)
        <span class="ms-consolidated__review">
            <span @class(['ms-consolidated__review-value', 'ms-consolidated__negative' => $signed && round($value) < 0])>
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z" />
                    <path d="M12 9v4M12 17h.01" />
                </svg>
                {{ $formatted }}
            </span>
            <span class="ms-consolidated__review-note">valore da verificare</span>
        </span>
    @else
        <span @class(['ms-consolidated__negative' => $signed && round($value) < 0])>{{ $formatted }}</span>
    @endif
@endif
