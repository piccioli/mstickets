{{-- Controparte testuale del layout condiviso (base.blade.php, US-310): ogni email
     ha SEMPRE questa versione oltre all'HTML (§7.5.4 del PRD), mai solo HTML. --}}
@yield('content')

--
Montagna Servizi SCPA - Via Errico Petrella 19, 20124 Milano (MI)
P.IVA 11790660960 - SDI: M5UXCR1
info@montagnaservizi.com
@php($preferencesUrl = config('mail_pipeline.notification_preferences_url'))
@if(filled($preferencesUrl))
Gestisci le preferenze di notifica: {{ $preferencesUrl }}
@endif
