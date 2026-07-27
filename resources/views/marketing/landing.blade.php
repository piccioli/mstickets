<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Montagna Servizi — Piattaforma Servizi CAI</title>
    @vite(['resources/css/marketing.css'])
</head>
<body class="mkt">
    <header class="mkt-navbar">
        <div class="mkt-navbar__inner">
            <img src="{{ asset('images/branding/montagna-servizi-logo.png') }}" alt="Montagna Servizi">
            <a href="{{ route('filament.admin.auth.login') }}" class="mkt-btn mkt-btn--primary">
                Accedi
            </a>
        </div>
    </header>

    <section class="mkt-hero">
        <img src="{{ asset('images/marketing/hero-alpine.svg') }}" alt="" class="mkt-hero__bg">
        <div class="mkt-hero__overlay"></div>
        <div class="mkt-hero__content">
            <span class="mkt-hero__eyebrow">Piattaforma Servizi CAI</span>
            <h1 class="mkt-hero__title">Concentrati su ciò che conta: la montagna e la comunità.</h1>
            <p class="mkt-hero__subtitle">
                La piattaforma di Montagna Servizi per gestire documenti, riunioni, bandi e progetti
                della tua Sezione — in un unico posto, sempre a disposizione della tua Sezione.
            </p>
            <a href="{{ route('filament.admin.auth.login') }}" class="mkt-btn mkt-btn--primary" style="margin-top: 0.5rem;">
                Accedi
            </a>
        </div>
    </section>

    <footer class="mkt-footer">
        <div class="mkt-footer__inner">
            <img src="{{ asset('images/branding/montagna-servizi-logo-white.png') }}" alt="Montagna Servizi" style="height: 40px; width: auto;">
            <div style="font-size: var(--text-sm); line-height: var(--leading-relaxed); color: rgba(255,255,255,0.7);">
                <strong style="color: #fff;">Sede legale:</strong><br>
                Via Errico Petrella 19<br>
                20124 Milano (MI)<br>
                P.IVA 11790660960 · SDI: M5UXCR1
            </div>
            <div style="font-size: var(--text-sm); display: flex; flex-direction: column; gap: 4px;">
                <a href="mailto:info@montagnaservizi.com">info@montagnaservizi.com</a>
                <a href="mailto:montagnaserviziscpa@legalmail.it">PEC: montagnaserviziscpa@legalmail.it</a>
            </div>
        </div>
        <div class="mkt-footer__legal">
            <span>&copy; {{ now()->year }} Montagna Servizi SCPA — Tutti i diritti riservati</span>
        </div>
    </footer>
</body>
</html>
