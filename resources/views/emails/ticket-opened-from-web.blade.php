{{--
    E2 (§7.5.2, US-311, nuovo): conferma al richiedente che il ticket aperto
    dal pannello web è stato registrato. Layout condiviso (US-310).
--}}
@extends('emails.layouts.base')

@section('content')
    <x-emails.ticket-header :ticket-id="$ticket->id" :title="$ticket->title" />

    <p style="margin:0 0 16px;">
        <x-emails.status-badge :status="$ticket->status" />
    </p>

    <p style="margin:0 0 20px;font-size:13.5px;line-height:1.5;color:{{ \App\Support\DesignTokens::get('ms-text-body') }};">
        Il tuo ticket #{{ $ticket->id }} è stato aperto correttamente. Ti aggiorneremo non appena ci sono novità.
    </p>

    <x-emails.cta-button label="Vai al ticket" :url="$portalUrl" />
@endsection
