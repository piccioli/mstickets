{{--
    E1 (§7.5.2, US-311): conferma al mittente che la sua email ha aperto un
    nuovo ticket. Layout condiviso (US-310) — @extends('emails.layouts.base').
--}}
@extends('emails.layouts.base')

@section('content')
    <x-emails.ticket-header :ticket-id="$ticket->id" :title="$ticket->title" />

    <p style="margin:0 0 16px;">
        <x-emails.status-badge :status="$ticket->status" />
    </p>

    <p style="margin:0 0 20px;font-size:13.5px;line-height:1.5;color:{{ \App\Support\DesignTokens::get('ms-text-body') }};">
        {{ __('We received your request by email and opened ticket #:id. We will update you as soon as there is news.', ['id' => $ticket->id]) }}
    </p>

    <x-emails.cta-button :label="__('Go to ticket')" :url="$portalUrl" />
@endsection
