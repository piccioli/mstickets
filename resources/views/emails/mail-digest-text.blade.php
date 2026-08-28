{{-- Controparte testuale di mail-digest.blade.php (US-614). --}}
@extends('emails.layouts.base-text')

@section('content')
{{ __('Here is a summary of the activity on your tickets in the last 24 hours.') }}

@foreach ($rows as $row)
@php($entry = $row['entry'])
{{ __('Ticket #:id', ['id' => $entry->ticket->id]) }} - {{ $entry->ticket->title }}
@if ($entry->hasStatusChange())
{{ mb_strtoupper($entry->previousStatus->getLabel()) }} -> {{ mb_strtoupper($entry->currentStatus->getLabel()) }}
@endif
@if ($entry->newMessagesCount > 0)
{{ __('New messages: :count', ['count' => $entry->newMessagesCount]) }}
@endif
{{ __('Go to ticket') }}: {{ $row['url'] }}

@endforeach
@endsection
