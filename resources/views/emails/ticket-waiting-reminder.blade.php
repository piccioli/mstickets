{{--
    E7 (§7.5.2, US-316): promemoria ticket in attesa senza attività da giorni.
    Layout condiviso (US-310) — @extends('emails.layouts.base').
--}}
@extends('emails.layouts.base')

@section('content')
    <x-emails.ticket-header :ticket-id="$ticket->id" :title="$ticket->title" />

    <p style="margin:0 0 20px;font-size:13.5px;line-height:1.5;color:{{ \App\Support\DesignTokens::get('ms-text-body') }};">
        Il ticket #{{ $ticket->id }} è in attesa di un tuo riscontro da qualche giorno. Se non serve più
        attenzione da parte tua, o se puoi fornirci le informazioni richieste, rispondi a questa email o
        vai al ticket.
    </p>

    <x-emails.cta-button label="Vai al ticket" :url="$portalUrl" />
@endsection
