{{--
    Vista PDF di una pagina di documentazione (§6.4.3 del PRD, US-406). Deriva
    logo/palette/tipografia da App\Support\DesignTokens (resources/css/theme.css),
    la STESSA fonte già usata dal layout email (resources/views/emails/layouts/base.blade.php)
    e dal tema Filament — zero valori hex riscritti una seconda volta.

    Tabelle + stili inline (nessuna classe Tailwind, nessun <link>/<style> esterno):
    il driver "chrome" renderizza HTML statico via Chrome DevTools Protocol
    (Page::setHtml), senza servire gli asset compilati in public/build/ né una
    richiesta HTTP verso l'app stessa — stesso vincolo già rispettato dal layout
    email per compatibilità coi client di posta, qui per indipendenza dalla rete.

    $page->body arriva già sanitizzato da TicketMessageSanitizer (vedi il mutator
    su DocumentationPage::body()), stampato con {!! !!} senza ulteriore escaping
    — stesso principio di resources/views/components/emails/message-block.blade.php.
--}}
<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <title>{{ $page->title }}</title>
</head>
<body style="margin:0;padding:0;background-color:#ffffff;font-family:'{{ \App\Support\DesignTokens::primaryFontFamily() }}',Arial,sans-serif;color:{{ \App\Support\DesignTokens::get('ms-text-body') }};">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding:0 0 20px;border-bottom:1px solid {{ \App\Support\DesignTokens::get('ms-border-subtle') }};">
                @if ($logoDataUri)
                    <img src="{{ $logoDataUri }}" alt="Montagna Servizi" height="28" style="display:block;height:28px;width:auto;border:0;">
                @else
                    <span style="font-size:14px;font-weight:700;color:{{ \App\Support\DesignTokens::get('ms-brand') }};">Montagna Servizi</span>
                @endif
            </td>
        </tr>
        <tr>
            <td style="padding:28px 0 8px;">
                <h1 style="margin:0;font-size:20px;line-height:1.3;color:{{ \App\Support\DesignTokens::get('ms-text-heading') }};">{{ $page->title }}</h1>
            </td>
        </tr>
        <tr>
            <td style="padding:0 0 32px;font-size:13.5px;line-height:1.6;color:{{ \App\Support\DesignTokens::get('ms-text-body') }};">
                {!! $page->body !!}
            </td>
        </tr>
        <tr>
            <td style="padding:16px 0 0;border-top:1px solid {{ \App\Support\DesignTokens::get('ms-border-subtle') }};font-size:10.5px;line-height:1.5;color:{{ \App\Support\DesignTokens::get('ms-text-muted') }};">
                {{ $footer }}
            </td>
        </tr>
    </table>
</body>
</html>
