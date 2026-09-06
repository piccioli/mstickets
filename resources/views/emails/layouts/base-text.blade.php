{{-- Controparte testuale del layout condiviso (base.blade.php, US-310): ogni email
     ha SEMPRE questa versione oltre all'HTML (§7.5.4 del PRD), mai solo HTML. --}}
@yield('content')

--
Montagna Servizi SCPA - Via Decorati al Valor Civile 15, 20138 Milano (MI)
P.IVA 11790660960 - SDI: M5UXCR1
info@montagnaservizi.com - +39 02 82197148
@php($preferencesUrl = config('mail_pipeline.notification_preferences_url'))
@if(filled($preferencesUrl))
{{ __('Manage notification preferences') }}: {{ $preferencesUrl }}
@endif
