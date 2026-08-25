{{--
    Vista di riferimento (US-310) che dimostra il layout condiviso + tutti i
    componenti Blade riusabili con un Mailable reale (Tests\Support\Mail\
    ExampleTicketNotificationMail). Le comunicazioni reali del catalogo
    (E1-E7/E9) arrivano da US-311 in poi: quella story userà lo stesso layout/
    componenti da una vista propria per ogni tipo di comunicazione, seguendo
    questo stesso pattern (@extends('emails.layouts.base') + componenti).
--}}
@extends('emails.layouts.base')

@section('content')
    <x-emails.ticket-header :ticket-id="$ticket->id" :title="$ticket->title" />

    <p style="margin:0 0 16px;">
        <x-emails.status-badge :status="$ticket->status" />
    </p>

    <x-emails.message-block
        :author-name="$authorName"
        :occurred-at="$occurredAt"
        :body-html="$bodyHtml"
    />

    <x-emails.cta-button :label="$ctaLabel" :url="$ctaUrl" />
@endsection
