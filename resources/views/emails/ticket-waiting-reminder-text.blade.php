{{-- Controparte testuale di ticket-waiting-reminder.blade.php (US-316). --}}
@extends('emails.layouts.base-text')

@section('content')
Ticket #{{ $ticket->id }} - {{ $ticket->title }}

Il ticket #{{ $ticket->id }} è in attesa di un tuo riscontro da qualche giorno. Se non serve più attenzione
da parte tua, o se puoi fornirci le informazioni richieste, rispondi a questa email o vai al ticket.

Vai al ticket: {{ $portalUrl }}
@endsection
