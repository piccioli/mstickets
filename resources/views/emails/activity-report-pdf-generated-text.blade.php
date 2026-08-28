{{-- Controparte testuale di activity-report-pdf-generated.blade.php (US-615). --}}
@extends('emails.layouts.base-text')

@section('content')
{{ __('Your activity report for :period is ready.', ['period' => $periodLabel]) }}

{{ __('Download the PDF') }}: {{ $downloadUrl }}
@endsection
