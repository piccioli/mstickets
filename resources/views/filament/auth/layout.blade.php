@props(['livewire' => null])
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ trim(strip_tags($livewire?->getTitle() ?? '')) }} - Montagna Servizi</title>
    <link rel="icon" href="{{ asset('images/branding/montagna-servizi-mark.png') }}">

    {{-- Layout dedicato alle pagine pubbliche (login/recupero password, v0.3.0): NON carica
         il tema Vite del pannello (@filamentStyles porterebbe con sé il teal/Nunito Sans di
         resources/css/filament/admin/theme.css, US-004/US-005) — solo lo stylesheet
         "marketing" con i token verde pino/Manrope. Le azioni reali (autenticazione, rate
         limiting, reset password) restano quelle native Filament: manteniamo @filamentScripts
         (Livewire + Alpine, nessuna dipendenza CSS) e il componente Notifications per le
         notifiche native (es. throttling dopo tentativi falliti). --}}
    @vite(['resources/css/marketing.css'])
</head>
<body class="mkt">
    {{ $slot }}

    @livewire(Filament\Livewire\Notifications::class)

    @filamentScripts(withCore: true)
</body>
</html>
