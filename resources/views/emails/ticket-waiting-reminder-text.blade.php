{{-- Controparte testuale di ticket-waiting-reminder.blade.php (US-316). --}}
@extends('emails.layouts.base-text')

@section('content')
{{ __('Ticket #:id', ['id' => $ticket->id]) }} - {{ $ticket->title }}

{{ __('Ticket #:id has been waiting for your feedback for a few days. If it no longer needs your attention, or if you can provide the requested information, reply to this email or go to the ticket.', ['id' => $ticket->id]) }}

{{ __('Go to ticket') }}: {{ $portalUrl }}
@endsection
