{{-- Bottone call-to-action riusabile (§7.5.4, US-310). --}}
@props(['label', 'url'])
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px 0 0;">
    <tr>
        <td style="border-radius:7px;background-color:{{ \App\Support\DesignTokens::get('ms-brand') }};">
            <a href="{{ $url }}" style="display:inline-block;padding:11px 22px;font-size:13.5px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:7px;">
                {{ $label }}
            </a>
        </td>
    </tr>
</table>
