{{-- Controparte testuale di ticket-assigned.blade.php (US-315). --}}
@extends('emails.layouts.base-text')

@section('content')
Ticket #{{ $ticket->id }} - {{ $ticket->title }}

@if ($asTester)
Ti è stato assegnato come tester il ticket #{{ $ticket->id }}.
@else
Ti è stato assegnato il ticket #{{ $ticket->id }}.
@endif

Vai al ticket: {{ $portalUrl }}
@endsection
