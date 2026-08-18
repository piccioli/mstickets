{{-- Controparte testuale di ticket-received-by-email.blade.php (US-311). --}}
@extends('emails.layouts.base-text')

@section('content')
Ticket #{{ $ticket->id }} - {{ $ticket->title }}
Stato: {{ mb_strtoupper($ticket->status->getLabel()) }}

Abbiamo ricevuto la tua richiesta via email e abbiamo aperto il ticket #{{ $ticket->id }}. Ti aggiorneremo non appena ci sono novità.

Vai al ticket: {{ $portalUrl }}
@endsection
