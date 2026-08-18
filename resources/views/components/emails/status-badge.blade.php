{{-- Badge di stato riusabile (§7.5.4, US-310): riusa la stessa categorizzazione
     colore di TicketStatus::getColor() via EmailStatusBadgePalette, non una
     seconda palette (vedi il commento su quella classe). --}}
@props(['status'])
@php($colors = \App\Domain\Mail\Support\EmailStatusBadgePalette::colors($status))
<span style="display:inline-block;padding:3px 11px;border-radius:99px;font-size:11px;font-weight:800;background-color:{{ $colors['background'] }};border:1px solid {{ $colors['border'] }};color:{{ $colors['text'] }};">
    {{ mb_strtoupper($status->getLabel()) }}
</span>
