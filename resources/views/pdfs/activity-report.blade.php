{{--
    Vista PDF di un report di attività (§6.5.3 del PRD, US-409). Stesso principio
    di resources/views/pdfs/documentation-page.blade.php (US-406): tabelle +
    stili inline, nessuna classe Tailwind/asset esterno (il driver "chrome"
    renderizza HTML statico via CDP, nessuna richiesta HTTP verso l'app), token
    di design da App\Support\DesignTokens, logo come data URI.

    Il locale della vista è impostato da GenerateActivityReportPdf (App::setLocale
    prima del render, ripristinato dopo) sulla base di $report->locale — tutte le
    stringhe statiche passano da __() per essere tradotte in lang/it.json e
    lang/en.json, coerente con il layout email (§7.6, US-320).
--}}
<!doctype html>
<html lang="{{ $report->locale }}">
<head>
    <meta charset="utf-8">
    <title>{{ $report->ownerName() }} — {{ $report->periodLabel() }}</title>
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
            <td style="padding:28px 0 4px;">
                <span style="font-size:11px;letter-spacing:.04em;text-transform:uppercase;color:{{ \App\Support\DesignTokens::get('ms-text-muted') }};">{{ __('Activity report') }}</span>
            </td>
        </tr>
        <tr>
            <td style="padding:0 0 4px;">
                <h1 style="margin:0;font-size:20px;line-height:1.3;color:{{ \App\Support\DesignTokens::get('ms-text-heading') }};">{{ $report->ownerName() }}</h1>
            </td>
        </tr>
        <tr>
            <td style="padding:0 0 24px;font-size:13.5px;color:{{ \App\Support\DesignTokens::get('ms-text-muted') }};">
                {{ $report->periodLabel() }}
            </td>
        </tr>
        <tr>
            <td style="padding:0 0 32px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:12.5px;">
                    <thead>
                        <tr>
                            <th align="left" style="padding:0 8px 8px 0;border-bottom:1px solid {{ \App\Support\DesignTokens::get('ms-border-subtle') }};color:{{ \App\Support\DesignTokens::get('ms-text-muted') }};font-weight:600;">{{ __('Ticket') }}</th>
                            <th align="left" style="padding:0 8px 8px;border-bottom:1px solid {{ \App\Support\DesignTokens::get('ms-border-subtle') }};color:{{ \App\Support\DesignTokens::get('ms-text-muted') }};font-weight:600;">{{ __('Type') }}</th>
                            <th align="left" style="padding:0 8px 8px;border-bottom:1px solid {{ \App\Support\DesignTokens::get('ms-border-subtle') }};color:{{ \App\Support\DesignTokens::get('ms-text-muted') }};font-weight:600;">{{ __('Opened on') }}</th>
                            <th align="left" style="padding:0 8px 8px;border-bottom:1px solid {{ \App\Support\DesignTokens::get('ms-border-subtle') }};color:{{ \App\Support\DesignTokens::get('ms-text-muted') }};font-weight:600;">{{ __('Completed on') }}</th>
                            <th align="right" style="padding:0 0 8px 8px;border-bottom:1px solid {{ \App\Support\DesignTokens::get('ms-border-subtle') }};color:{{ \App\Support\DesignTokens::get('ms-text-muted') }};font-weight:600;">{{ __('Hours') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tickets as $ticket)
                            <tr>
                                <td style="padding:8px 8px 8px 0;border-bottom:1px solid {{ \App\Support\DesignTokens::get('ms-border-subtle') }};color:{{ \App\Support\DesignTokens::get('ms-text-body') }};">#{{ $ticket->id }} — {{ $ticket->title }}</td>
                                <td style="padding:8px;border-bottom:1px solid {{ \App\Support\DesignTokens::get('ms-border-subtle') }};color:{{ \App\Support\DesignTokens::get('ms-text-body') }};">{{ $ticket->type->getLabel() }}</td>
                                <td style="padding:8px;border-bottom:1px solid {{ \App\Support\DesignTokens::get('ms-border-subtle') }};color:{{ \App\Support\DesignTokens::get('ms-text-body') }};">{{ $ticket->created_at?->translatedFormat('d/m/Y') }}</td>
                                <td style="padding:8px;border-bottom:1px solid {{ \App\Support\DesignTokens::get('ms-border-subtle') }};color:{{ \App\Support\DesignTokens::get('ms-text-body') }};">{{ $ticket->done_at?->translatedFormat('d/m/Y') }}</td>
                                <td align="right" style="padding:8px 0 8px 8px;border-bottom:1px solid {{ \App\Support\DesignTokens::get('ms-border-subtle') }};color:{{ \App\Support\DesignTokens::get('ms-text-body') }};">{{ number_format($ticket->worked_minutes / 60, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="padding:16px 0;color:{{ \App\Support\DesignTokens::get('ms-text-muted') }};">{{ __('No tickets in this period.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($tickets->isNotEmpty())
                        <tfoot>
                            <tr>
                                <td colspan="4" style="padding:12px 8px 0 0;text-align:right;font-weight:600;color:{{ \App\Support\DesignTokens::get('ms-text-heading') }};">{{ __('Total hours') }}</td>
                                <td align="right" style="padding:12px 0 0 8px;font-weight:600;color:{{ \App\Support\DesignTokens::get('ms-text-heading') }};">{{ number_format($totalWorkedMinutes / 60, 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
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
