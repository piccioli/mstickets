{{-- Controparte testuale di unknown-sender-staff.blade.php (US-312). --}}
@extends('emails.layouts.base-text')

@section('content')
{{ __('Unknown sender') }}
{{ $subject !== '' ? $subject : __('(no subject)') }}

{{ __('A message from :email does not match any registered user and has been quarantined.', ['email' => $fromEmail]) }}

{{ $bodyExcerpt !== '' ? $bodyExcerpt : __('(empty body)') }}
@if ($reviewUrl !== null)

{{ __('Go to quarantine') }}: {{ $reviewUrl }}
@endif
@endsection
