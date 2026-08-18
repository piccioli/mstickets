{{-- Controparte testuale di ticket-status-changed.blade.php (US-313). --}}
@extends('emails.layouts.base-text')

@section('content')
{{ __('Ticket #:id', ['id' => $ticket->id]) }} - {{ $ticket->title }}
{{ __('New status:') }} {{ mb_strtoupper($newStatus->getLabel()) }}

@if ($recipientIsCustomer)
{{ __('Your ticket #:id changed from :previous to :new.', ['id' => $ticket->id, 'previous' => $previousStatus->getLabel(), 'new' => $newStatus->getLabel()]) }}
@else
{{ __('Ticket #:id from :requester changed from :previous to :new.', ['id' => $ticket->id, 'requester' => $ticket->requester?->name ?? __('a requester'), 'previous' => $previousStatus->getLabel(), 'new' => $newStatus->getLabel()]) }}
@endif

{{ __('Go to ticket') }}: {{ $portalUrl }}
@endsection
