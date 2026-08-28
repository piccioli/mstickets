{{--
    E10 (§7.5.2, US-615): avviso di PDF report attività pronto.
    Layout condiviso (US-310) — @extends('emails.layouts.base').
--}}
@extends('emails.layouts.base')

@section('content')
    <p style="margin:0 0 20px;font-size:13.5px;line-height:1.5;color:{{ \App\Support\DesignTokens::get('ms-text-body') }};">
        {{ __('Your activity report for :period is ready.', ['period' => $periodLabel]) }}
    </p>

    <a href="{{ $downloadUrl }}" style="display:inline-block;font-size:13px;font-weight:700;color:#fff;background-color:{{ \App\Support\DesignTokens::get('ms-brand') }};padding:10px 18px;border-radius:6px;text-decoration:none;">
        {{ __('Download the PDF') }}
    </a>
@endsection
