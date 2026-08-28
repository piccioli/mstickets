{{--
    E11 (§7.5.2, US-616): promemoria interno, un blocco per ticket in coda
    ($rows: ['ticket' => Ticket, 'url' => string]). Layout condiviso
    (US-310) — @extends('emails.layouts.base').
--}}
@extends('emails.layouts.base')

@section('content')
    <p style="margin:0 0 20px;font-size:13.5px;line-height:1.5;color:{{ \App\Support\DesignTokens::get('ms-text-body') }};">
        {{ __('You have tickets assigned with nothing currently in progress. Here they are, so you can pick one up.') }}
    </p>

    @foreach ($rows as $row)
        @php($ticket = $row['ticket'])
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 16px;border:1px solid {{ \App\Support\DesignTokens::get('ms-border-subtle') }};border-radius:8px;">
            <tr>
                <td style="padding:16px 18px;">
                    <p style="margin:0 0 4px;font-size:11.5px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;color:{{ \App\Support\DesignTokens::get('ms-text-muted') }};">
                        {{ __('Ticket #:id', ['id' => $ticket->id]) }}
                    </p>
                    <p style="margin:0 0 10px;font-size:15px;line-height:1.3;font-weight:800;color:{{ \App\Support\DesignTokens::get('ms-text-heading') }};">
                        {{ $ticket->title }}
                    </p>

                    <p style="margin:0 0 10px;">
                        <x-emails.status-badge :status="$ticket->status" />
                    </p>

                    <a href="{{ $row['url'] }}" style="font-size:13px;font-weight:700;color:{{ \App\Support\DesignTokens::get('ms-brand') }};text-decoration:none;">
                        {{ __('Go to ticket') }} &rarr;
                    </a>
                </td>
            </tr>
        </table>
    @endforeach
@endsection
