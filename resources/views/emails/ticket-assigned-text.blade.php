{{-- Controparte testuale di ticket-assigned.blade.php (US-315). --}}
@extends('emails.layouts.base-text')

@section('content')
{{ __('Ticket #:id', ['id' => $ticket->id]) }} - {{ $ticket->title }}

@if ($asTester)
{{ __('You have been assigned as :role to ticket #:id.', ['role' => 'tester', 'id' => $ticket->id]) }}
@else
{{ __('You have been assigned to ticket #:id.', ['id' => $ticket->id]) }}
@endif

{{ __('Go to ticket') }}: {{ $portalUrl }}
@endsection
