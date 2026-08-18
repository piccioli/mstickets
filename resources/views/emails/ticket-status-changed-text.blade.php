{{-- Controparte testuale di ticket-status-changed.blade.php (US-313). --}}
@extends('emails.layouts.base-text')

@section('content')
Ticket #{{ $ticket->id }} - {{ $ticket->title }}
Nuovo stato: {{ mb_strtoupper($newStatus->getLabel()) }}

@if ($recipientIsCustomer)
Il tuo ticket #{{ $ticket->id }} è passato da {{ $previousStatus->getLabel() }} a {{ $newStatus->getLabel() }}.
@else
Il ticket #{{ $ticket->id }} di {{ $ticket->requester?->name ?? 'un richiedente' }} è passato da {{ $previousStatus->getLabel() }} a {{ $newStatus->getLabel() }}.
@endif

Vai al ticket: {{ $portalUrl }}
@endsection
