{{-- Controparte testuale di idle-developer-notice.blade.php (US-616). --}}
@extends('emails.layouts.base-text')

@section('content')
{{ __('You have tickets assigned with nothing currently in progress. Here they are, so you can pick one up.') }}

@foreach ($rows as $row)
@php($ticket = $row['ticket'])
{{ __('Ticket #:id', ['id' => $ticket->id]) }} - {{ $ticket->title }}
{{ mb_strtoupper($ticket->status->getLabel()) }}
{{ __('Go to ticket') }}: {{ $row['url'] }}

@endforeach
@endsection
