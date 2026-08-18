{{--
    E7 (§7.5.2, US-316): promemoria ticket in attesa senza attività da giorni.
    Layout condiviso (US-310) — @extends('emails.layouts.base').
--}}
@extends('emails.layouts.base')

@section('content')
    <x-emails.ticket-header :ticket-id="$ticket->id" :title="$ticket->title" />

    <p style="margin:0 0 20px;font-size:13.5px;line-height:1.5;color:{{ \App\Support\DesignTokens::get('ms-text-body') }};">
        {{ __('Ticket #:id has been waiting for your feedback for a few days. If it no longer needs your attention, or if you can provide the requested information, reply to this email or go to the ticket.', ['id' => $ticket->id]) }}
    </p>

    <x-emails.cta-button :label="__('Go to ticket')" :url="$portalUrl" />
@endsection
