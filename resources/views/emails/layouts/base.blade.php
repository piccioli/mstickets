{{--
    Layout unico per tutte le email della piattaforma (§7.5.4 del PRD, US-310).
    Deriva logo/palette/tipografia da App\Support\DesignTokens (resources/css/theme.css),
    la STESSA fonte usata dal tema Filament — zero valori hex riscritti a mano una
    seconda volta. Tabelle + stili inline per compatibilità con i client di posta
    (nessun <link>/<style> esterno, molti client li ignorano o li rimuovono).

    Ogni Mailable estende questo layout con `@extends('emails.layouts.base')` e
    riempie `@section('content')` componendo i componenti Blade riusabili di
    resources/views/components/emails/*.
--}}
<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>{{ config('app.name') }}</title>
</head>
<body style="margin:0;padding:0;background-color:{{ \App\Support\DesignTokens::get('ms-surface-page') }};font-family:'{{ \App\Support\DesignTokens::primaryFontFamily() }}',Arial,sans-serif;color:{{ \App\Support\DesignTokens::get('ms-text-body') }};">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:{{ \App\Support\DesignTokens::get('ms-surface-page') }};padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:{{ \App\Support\DesignTokens::get('ms-surface-card') }};border:1px solid {{ \App\Support\DesignTokens::get('ms-border-subtle') }};border-radius:10px;">
                    <tr>
                        <td style="padding:24px 32px;border-bottom:1px solid {{ \App\Support\DesignTokens::get('ms-border-subtle') }};">
                            <img src="{{ asset('images/branding/montagna-servizi-logo.png') }}" alt="Montagna Servizi" height="28" style="display:block;height:28px;width:auto;border:0;">
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;font-size:13.5px;line-height:1.5;">
                            @yield('content')
                        </td>
                    </tr>
                </table>
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">
                    <tr>
                        <td style="padding:20px 32px 0;">
                            <x-emails.footer />
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
