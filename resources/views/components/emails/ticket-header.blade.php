{{-- Intestazione ticket riusabile (§7.5.4, US-310): numero ticket (id, token [#id]
     coerente col resto della pipeline, US-306) + titolo. --}}
@props(['ticketId', 'title'])
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px;">
    <tr>
        <td>
            <p style="margin:0 0 4px;font-size:11.5px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;color:{{ \App\Support\DesignTokens::get('ms-text-muted') }};">
                Ticket #{{ $ticketId }}
            </p>
            <h1 style="margin:0;font-size:20px;line-height:1.2;font-weight:800;color:{{ \App\Support\DesignTokens::get('ms-text-heading') }};">
                {{ $title }}
            </h1>
        </td>
    </tr>
</table>
