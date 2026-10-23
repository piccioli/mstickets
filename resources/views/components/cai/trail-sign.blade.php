{{-- Numero del sentiero come segnavia CAI bianco-rosso-bianco (design "Sentieri di Sezione"): `ref` osm2cai, "—" se
     assente. `size`: `sm` (popup della mappa), `md` (righe dell'elenco), `lg` (card mobile). Stili in cai-trails.css. --}}
@props([
    'ref' => null,
    'size' => 'md',
])
<span {{ $attributes->class(['ms-cai-trail-sign', 'ms-cai-trail-sign--'.$size]) }}>{{ filled($ref) ? $ref : '—' }}</span>
