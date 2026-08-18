{{-- Controparte testuale di new-ticket-message.blade.php (US-314). --}}
@extends('emails.layouts.base-text')

@section('content')
{{ __('Ticket #:id', ['id' => $ticket->id]) }} - {{ $ticket->title }}

{{ __('New message on ticket #:id.', ['id' => $ticket->id]) }}

{{ $authorName }} - {{ $occurredAt instanceof \DateTimeInterface ? $occurredAt->format('d/m/Y H:i') : $occurredAt }}
{{ strip_tags($bodyHtml) }}

{{ __('Go to ticket') }}: {{ $portalUrl }}
@endsection
