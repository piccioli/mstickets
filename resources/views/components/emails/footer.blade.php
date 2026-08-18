{{--
    Footer riusabile con dati societari (§7.5.4, US-310), incluso in
    emails/layouts/base.blade.php per ogni email (zero duplicazione tra
    template). Link alle preferenze di notifica (US-317) mostrato solo se
    config('mail_pipeline.notification_preferences_url') è valorizzato: la UI
    per gestirle resta fuori scope fino alla Fase 6 (vedi prd.json), un link
    verso una pagina inesistente non deve mai comparire (stesso principio già
    applicato alla voce di menu Mailpit, US-324).
--}}
@props(['preferencesUrl' => config('mail_pipeline.notification_preferences_url')])
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td style="padding:16px 0;font-size:11.5px;line-height:1.5;color:{{ \App\Support\DesignTokens::get('ms-text-muted') }};text-align:center;">
            <p style="margin:0 0 8px;">
                Montagna Servizi SCPA &mdash; Via Errico Petrella 19, 20124 Milano (MI)<br>
                P.IVA 11790660960 &middot; SDI: M5UXCR1
            </p>
            <p style="margin:0 0 8px;">
                <a href="mailto:info@montagnaservizi.com" style="color:{{ \App\Support\DesignTokens::get('ms-brand') }};text-decoration:none;">info@montagnaservizi.com</a>
            </p>
            @if (filled($preferencesUrl))
                <p style="margin:0 0 8px;">
                    <a href="{{ $preferencesUrl }}" style="color:{{ \App\Support\DesignTokens::get('ms-brand') }};text-decoration:none;">Gestisci le preferenze di notifica</a>
                </p>
            @endif
            <p style="margin:0;">&copy; {{ now()->year }} Montagna Servizi SCPA</p>
        </td>
    </tr>
</table>
