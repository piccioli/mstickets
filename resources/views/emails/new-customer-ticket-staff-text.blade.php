{{-- Controparte testuale di new-customer-ticket-staff.blade.php (US-312). --}}
@extends('emails.layouts.base-text')

@section('content')
Ticket #{{ $ticket->id }} - {{ $ticket->title }}
Stato: {{ mb_strtoupper($ticket->status->getLabel()) }}

Un cliente ha aperto il ticket #{{ $ticket->id }}.

Vai al ticket: {{ $portalUrl }}
@endsection
