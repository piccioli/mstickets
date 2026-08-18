{{-- Controparte testuale di new-ticket-message.blade.php (US-314). --}}
@extends('emails.layouts.base-text')

@section('content')
Ticket #{{ $ticket->id }} - {{ $ticket->title }}

Nuovo messaggio sul ticket #{{ $ticket->id }}.

{{ $authorName }} - {{ $occurredAt instanceof \DateTimeInterface ? $occurredAt->format('d/m/Y H:i') : $occurredAt }}
{{ strip_tags($bodyHtml) }}

Vai al ticket: {{ $portalUrl }}
@endsection
