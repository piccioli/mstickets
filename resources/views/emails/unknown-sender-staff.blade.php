{{--
    E9 (§7.3.8/§7.5.2, US-308/US-312): notifica allo staff di un mittente non
    identificato. Layout condiviso (US-310) — @extends('emails.layouts.base').
    Nessun link se config('mail_pipeline.quarantine_review_url') è vuoto (US-322
    non ancora costruita): mai un link verso una pagina inesistente.
--}}
@extends('emails.layouts.base')

@section('content')
    <p style="margin:0 0 4px;font-size:11.5px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;color:{{ \App\Support\DesignTokens::get('ms-text-muted') }};">
        {{ __('Unknown sender') }}
    </p>
    <h1 style="margin:0 0 20px;font-size:20px;line-height:1.2;font-weight:800;color:{{ \App\Support\DesignTokens::get('ms-text-heading') }};">
        {{ $subject !== '' ? $subject : __('(no subject)') }}
    </h1>

    <p style="margin:0 0 12px;font-size:13.5px;line-height:1.5;color:{{ \App\Support\DesignTokens::get('ms-text-body') }};">
        {!! __('A message from :email does not match any registered user and has been quarantined.', ['email' => '<strong>'.e($fromEmail).'</strong>']) !!}
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px;border:1px solid {{ \App\Support\DesignTokens::get('ms-border-subtle') }};border-radius:8px;">
        <tr>
            <td style="padding:16px 20px;font-size:13px;line-height:1.5;color:{{ \App\Support\DesignTokens::get('ms-text-secondary') }};">
                {{ $bodyExcerpt !== '' ? $bodyExcerpt : __('(empty body)') }}
            </td>
        </tr>
    </table>

    @if ($reviewUrl !== null)
        <x-emails.cta-button :label="__('Go to quarantine')" :url="$reviewUrl" />
    @endif
@endsection
