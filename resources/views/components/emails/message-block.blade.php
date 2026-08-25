{{--
    Blocco messaggio riusabile (§7.5.4, US-310): autore + data + corpo.
    IMPORTANTE: $bodyHtml deve arrivare GIÀ sanitizzato dal chiamante (stesso
    principio allowlist di App\Domain\Ticketing\Support\TicketMessageSanitizer,
    US-106/US-303) — questo componente lo stampa con {!! !!} senza ulteriore
    escaping, non deve mai ricevere HTML non fidato.
--}}
@props(['authorName', 'occurredAt', 'bodyHtml'])
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;border:1px solid {{ \App\Support\DesignTokens::get('ms-border-subtle') }};border-radius:8px;">
    <tr>
        <td style="padding:16px 20px;">
            <p style="margin:0 0 10px;font-size:12.5px;color:{{ \App\Support\DesignTokens::get('ms-text-secondary') }};">
                <strong style="color:{{ \App\Support\DesignTokens::get('ms-text-strong') }};">{{ $authorName }}</strong>
                &middot;
                {{ $occurredAt instanceof \DateTimeInterface ? $occurredAt->format('d/m/Y H:i') : $occurredAt }}
            </p>
            <div style="font-size:13.5px;line-height:1.5;color:{{ \App\Support\DesignTokens::get('ms-text-body') }};">
                {!! $bodyHtml !!}
            </div>
        </td>
    </tr>
</table>
