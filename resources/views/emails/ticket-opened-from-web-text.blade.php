{{-- Controparte testuale di ticket-opened-from-web.blade.php (US-311). --}}
@extends('emails.layouts.base-text')

@section('content')
{{ __('Ticket #:id', ['id' => $ticket->id]) }} - {{ $ticket->title }}
{{ __('Status:') }} {{ mb_strtoupper($ticket->status->getLabel()) }}

{{ __('Your ticket #:id has been opened successfully. We will update you as soon as there is news.', ['id' => $ticket->id]) }}

{{ __('Go to ticket') }}: {{ $portalUrl }}
@endsection
