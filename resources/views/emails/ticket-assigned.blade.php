{{--
    E6 (§7.5.2, US-315): assegnazione del ticket a un developer o a un tester.
    Layout condiviso (US-310) — @extends('emails.layouts.base').
--}}
@extends('emails.layouts.base')

@section('content')
    <x-emails.ticket-header :ticket-id="$ticket->id" :title="$ticket->title" />

    @if ($asTester)
        <p style="margin:0 0 20px;font-size:13.5px;line-height:1.5;color:{{ \App\Support\DesignTokens::get('ms-text-body') }};">
            {!! __('You have been assigned as :role to ticket #:id.', ['role' => '<strong>tester</strong>', 'id' => $ticket->id]) !!}
        </p>
    @else
        <p style="margin:0 0 20px;font-size:13.5px;line-height:1.5;color:{{ \App\Support\DesignTokens::get('ms-text-body') }};">
            {{ __('You have been assigned to ticket #:id.', ['id' => $ticket->id]) }}
        </p>
    @endif

    <x-emails.cta-button :label="__('Go to ticket')" :url="$portalUrl" />
@endsection
