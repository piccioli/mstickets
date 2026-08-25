{{-- Controparte testuale di ticket-notification.blade.php (US-310). --}}
@extends('emails.layouts.base-text')

@section('content')
Ticket #{{ $ticket->id }} - {{ $ticket->title }}
Stato: {{ mb_strtoupper($ticket->status->getLabel()) }}

{{ $authorName }} ({{ $occurredAt instanceof \DateTimeInterface ? $occurredAt->format('d/m/Y H:i') : $occurredAt }}):
{{ \App\Domain\Ticketing\Support\TicketMessageSanitizer::toPlainText($bodyHtml) }}

{{ $ctaLabel }}: {{ $ctaUrl }}
@endsection
