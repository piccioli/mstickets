@props([
    'compact' => false,
    'tagline' => null,
])

<div class="mkt-auth">
    <div class="mkt-auth__card">
        <div class="mkt-auth__panel @if($compact) mkt-auth__panel--compact @endif">
            <img src="{{ asset('images/marketing/hero-alpine.svg') }}" alt="">
            <div class="mkt-auth__panel-overlay"></div>
            <div class="mkt-auth__panel-content">
                <img src="{{ asset('images/branding/montagna-servizi-logo-white.png') }}" alt="Montagna Servizi" class="mkt-logo">

                <div class="mkt-auth__panel-mobile-only">
                    {{ $mobile }}
                </div>

                <div class="mkt-auth__panel-desktop-only">
                    {{ $desktop }}
                </div>

                @if ($tagline)
                    <div class="mkt-auth__tagline mkt-auth__panel-desktop-only">{{ $tagline }}</div>
                @endif
            </div>
        </div>

        <div {{ $attributes->class(['mkt-auth__form']) }}>
            {{ $slot }}
        </div>
    </div>
</div>
