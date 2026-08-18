{{--
    E3 (§7.5.2, US-312): notifica allo staff di un nuovo ticket cliente.
    Layout condiviso (US-310) — @extends('emails.layouts.base').
--}}
@extends('emails.layouts.base')

@section('content')
    <x-emails.ticket-header :ticket-id="$ticket->id" :title="$ticket->title" />

    <p style="margin:0 0 16px;">
        <x-emails.status-badge :status="$ticket->status" />
    </p>

    <p style="margin:0 0 20px;font-size:13.5px;line-height:1.5;color:{{ \App\Support\DesignTokens::get('ms-text-body') }};">
        Un cliente ha aperto il ticket #{{ $ticket->id }}.
    </p>

    <x-emails.cta-button label="Vai al ticket" :url="$portalUrl" />
@endsection
