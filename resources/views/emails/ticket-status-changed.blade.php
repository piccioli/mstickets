{{--
    E4 (§7.5.2, US-313): cambio di stato del ticket, contenuto diverso per lo
    staff e per un cliente (corregge il problema 11 del v1). Layout condiviso
    (US-310) — @extends('emails.layouts.base').
--}}
@extends('emails.layouts.base')

@section('content')
    <x-emails.ticket-header :ticket-id="$ticket->id" :title="$ticket->title" />

    <p style="margin:0 0 16px;">
        <x-emails.status-badge :status="$newStatus" />
    </p>

    @if ($recipientIsCustomer)
        <p style="margin:0 0 20px;font-size:13.5px;line-height:1.5;color:{{ \App\Support\DesignTokens::get('ms-text-body') }};">
            Il tuo ticket #{{ $ticket->id }} è passato da <strong>{{ $previousStatus->getLabel() }}</strong> a <strong>{{ $newStatus->getLabel() }}</strong>.
        </p>
    @else
        <p style="margin:0 0 20px;font-size:13.5px;line-height:1.5;color:{{ \App\Support\DesignTokens::get('ms-text-body') }};">
            Il ticket #{{ $ticket->id }} di {{ $ticket->requester?->name ?? 'un richiedente' }} è passato da <strong>{{ $previousStatus->getLabel() }}</strong> a <strong>{{ $newStatus->getLabel() }}</strong>.
        </p>
    @endif

    <x-emails.cta-button label="Vai al ticket" :url="$portalUrl" />
@endsection
