{{-- Controparte testuale di unknown-sender-staff.blade.php (US-312). --}}
@extends('emails.layouts.base-text')

@section('content')
Mittente sconosciuto
{{ $subject !== '' ? $subject : '(nessun oggetto)' }}

Un messaggio da {{ $fromEmail }} non corrisponde a nessun utente registrato ed è stato messo in quarantena.

{{ $bodyExcerpt !== '' ? $bodyExcerpt : '(corpo vuoto)' }}
@if ($reviewUrl !== null)

Vai alla quarantena: {{ $reviewUrl }}
@endif
@endsection
