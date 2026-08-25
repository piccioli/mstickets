{{-- Controparte testuale di new-customer-ticket-staff.blade.php (US-312). --}}
@extends('emails.layouts.base-text')

@section('content')
{{ __('Ticket #:id', ['id' => $ticket->id]) }} - {{ $ticket->title }}
{{ __('Status:') }} {{ mb_strtoupper($ticket->status->getLabel()) }}

{{ __('A customer opened ticket #:id.', ['id' => $ticket->id]) }}

{{ __('Go to ticket') }}: {{ $portalUrl }}
@endsection
