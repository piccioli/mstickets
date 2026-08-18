{{-- Controparte testuale di ticket-opened-from-web.blade.php (US-311). --}}
@extends('emails.layouts.base-text')

@section('content')
Ticket #{{ $ticket->id }} - {{ $ticket->title }}
Stato: {{ mb_strtoupper($ticket->status->getLabel()) }}

Il tuo ticket #{{ $ticket->id }} è stato aperto correttamente. Ti aggiorneremo non appena ci sono novità.

Vai al ticket: {{ $portalUrl }}
@endsection
