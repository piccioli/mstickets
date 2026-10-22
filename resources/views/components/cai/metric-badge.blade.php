{{-- Badge metrica della Dashboard V2 della Sezione (Fase 10.1, US-1002), riusabile dalla Dashboard GR (10.2).
     Tutta la card è un link. Un valore `null` si mostra sempre come "—" + "Non disponibile", mai come 0.
     Varianti:
     - `semplice`: `label`, `value` (int|float|null), `unit` facoltativa, `decimals`, `note` facoltativa sotto il
       numero (es. "Somma di 42 Sezioni", mai mostrata se il valore manca);
     - `bilancio`: `title`, `mainLabel`, `mainValue`, `year`, `secondary` (1 o 2 voci `['label' => ..., 'value' => ...]`)
       e `result` (true solo per il conto economico: segno, pill Avanzo/Disavanzo, rosso se negativo); `yearNote`
       facoltativa accodata a "Esercizio {year}" con " · " (solo se l'esercizio è mostrato).
     Importi in euro, formattati da ItalianNumberFormatter; stili in resources/css/filament/admin/metric-badge.css. --}}
@props([
    'variant' => 'semplice',
    'href',
    'label' => null,
    'value' => null,
    'unit' => null,
    'decimals' => 0,
    'note' => null,
    'title' => null,
    'mainLabel' => null,
    'mainValue' => null,
    'year' => null,
    'yearNote' => null,
    'result' => false,
    'secondary' => [],
    'linkLabel' => 'Vedi dettaglio',
])
@use('App\Support\Formatting\ItalianNumberFormatter')
@php
    $isBalance = $variant === 'bilancio';
    $hasData = $isBalance
        ? $mainValue !== null || collect($secondary)->contains(fn (array $item): bool => $item['value'] !== null)
        : $value !== null;
    $isNegative = $isBalance && $result && $mainValue !== null && round((float) $mainValue) < 0;
@endphp
<a
    href="{{ $href }}"
    {{ $attributes->class([
        'ms-metric-badge',
        'ms-metric-badge--balance' => $isBalance,
        'ms-metric-badge--unavailable' => ! $hasData,
    ]) }}
>
    @if ($isBalance)
        <span class="ms-metric-badge__title">{{ $title }}</span>
        <span class="ms-metric-badge__main-label">
            {{ $mainLabel }}
            @if ($result && $mainValue !== null)
                <span @class(['ms-metric-badge__pill', 'ms-metric-badge__pill--deficit' => $isNegative])>
                    {{ $isNegative ? 'Disavanzo' : 'Avanzo' }}
                </span>
            @endif
        </span>
        @if ($mainValue !== null)
            <span @class(['ms-metric-badge__main-value', 'ms-metric-badge__main-value--negative' => $isNegative])>
                {{ $result ? ItalianNumberFormatter::signedEuro($mainValue) : ItalianNumberFormatter::euro($mainValue) }}
            </span>
        @else
            <span class="ms-metric-badge__main-value ms-metric-badge__dash">—</span>
            <span class="ms-metric-badge__missing">Non disponibile</span>
        @endif
        @if ($hasData && $year !== null)
            <span class="ms-metric-badge__year">Esercizio {{ $year }}@if ($yearNote !== null) · {{ $yearNote }}@endif</span>
        @endif
        @if ($hasData && $secondary !== [])
            <span class="ms-metric-badge__secondary">
                @foreach ($secondary as $item)
                    <span class="ms-metric-badge__secondary-item">
                        <span class="ms-metric-badge__secondary-label">{{ $item['label'] }}</span>
                        @if ($item['value'] !== null)
                            <span class="ms-metric-badge__secondary-value">{{ ItalianNumberFormatter::euro($item['value']) }}</span>
                        @else
                            <span class="ms-metric-badge__secondary-value ms-metric-badge__dash">—</span>
                            <span class="ms-metric-badge__missing">Non disponibile</span>
                        @endif
                    </span>
                @endforeach
            </span>
        @endif
    @else
        <span class="ms-metric-badge__label">{{ $label }}</span>
        @if ($value !== null)
            <span class="ms-metric-badge__value">
                <span class="ms-metric-badge__number">{{ ItalianNumberFormatter::decimal($value, $decimals) }}</span>
                @if ($unit !== null)
                    <span class="ms-metric-badge__unit">{{ $unit }}</span>
                @endif
            </span>
            @if ($note !== null)
                <span class="ms-metric-badge__note">{{ $note }}</span>
            @endif
        @else
            <span class="ms-metric-badge__value">
                <span class="ms-metric-badge__number ms-metric-badge__dash">—</span>
            </span>
            <span class="ms-metric-badge__missing">Non disponibile</span>
        @endif
    @endif
    <span class="ms-metric-badge__link">
        {{ $linkLabel }}
        <span class="ms-metric-badge__arrow" aria-hidden="true">→</span>
    </span>
</a>
