{{--
    E5 (§7.5.2, US-314): nuovo messaggio PUBBLICO sul ticket. Layout condiviso
    (US-310) — @extends('emails.layouts.base'). Non renderizzata MAI per un
    messaggio visibility=internal (guard nell'Action, non qui).
--}}
@extends('emails.layouts.base')

@section('content')
    <x-emails.ticket-header :ticket-id="$ticket->id" :title="$ticket->title" />

    <p style="margin:0 0 16px;font-size:13.5px;line-height:1.5;color:{{ \App\Support\DesignTokens::get('ms-text-body') }};">
        Nuovo messaggio sul ticket #{{ $ticket->id }}.
    </p>

    <x-emails.message-block :author-name="$authorName" :occurred-at="$occurredAt" :body-html="$bodyHtml" />

    <x-emails.cta-button label="Vai al ticket" :url="$portalUrl" />
@endsection
