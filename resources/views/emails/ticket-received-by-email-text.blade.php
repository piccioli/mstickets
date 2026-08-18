{{-- Controparte testuale di ticket-received-by-email.blade.php (US-311). --}}
@extends('emails.layouts.base-text')

@section('content')
{{ __('Ticket #:id', ['id' => $ticket->id]) }} - {{ $ticket->title }}
{{ __('Status:') }} {{ mb_strtoupper($ticket->status->getLabel()) }}

{{ __('We received your request by email and opened ticket #:id. We will update you as soon as there is news.', ['id' => $ticket->id]) }}

{{ __('Go to ticket') }}: {{ $portalUrl }}
@endsection
