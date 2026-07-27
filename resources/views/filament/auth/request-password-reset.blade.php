<div class="mkt-auth">
    <div class="mkt-auth__panel">
        <img src="{{ asset('images/marketing/hero-alpine.svg') }}" alt="">
        <div class="mkt-auth__panel-overlay"></div>
        <div class="mkt-auth__panel-content">
            <img src="{{ asset('images/branding/montagna-servizi-logo-white.png') }}" alt="Montagna Servizi" class="mkt-logo">

            <div>
                <div class="mkt-auth__eyebrow">Accesso sicuro</div>
                <h1 class="mkt-auth__title">Ti rimettiamo in cammino in pochi passaggi.</h1>
                <p class="mkt-auth__lead">
                    Per la sicurezza dei dati della tua Sezione il link di recupero è valido 60 minuti e
                    utilizzabile una sola volta.
                </p>
            </div>

            <div class="mkt-auth__tagline">Serve aiuto? Ti rispondiamo entro 48 ore.</div>
        </div>
    </div>

    <div class="mkt-auth__form">
        @if (! $linkSent)
            <div class="mkt-auth__form-eyebrow">Recupera password</div>
            <h2 class="mkt-auth__form-title">Hai dimenticato la password?</h2>
            <p class="mkt-auth__form-lead">
                Inserisci l'email con cui accedi alla piattaforma: ti inviamo un link per impostarne una
                nuova.
            </p>

            <form wire:submit="request">
                <div class="mkt-field" style="margin-bottom: 1.6rem;">
                    <label for="reset-request-email">Email</label>
                    <div class="fi-input-wrp" style="display: flex; align-items: center; padding: 0.15rem 1rem;">
                        <input
                            type="email"
                            id="reset-request-email"
                            wire:model="data.email"
                            required
                            autocomplete="email"
                            autofocus
                            placeholder="nome@sezione.cai.it"
                            style="flex: 1; border: none; outline: none; background: transparent; padding: 0.6rem 0; font: inherit;"
                        >
                    </div>
                </div>

                <button type="submit" class="mkt-btn mkt-btn--primary mkt-btn--full" wire:loading.attr="disabled" wire:target="request">
                    <span wire:loading.remove wire:target="request">Invia il link di recupero</span>
                    <span wire:loading wire:target="request">Invio in corso…</span>
                </button>
            </form>

            <div style="margin-top: 1.5rem;">
                <a href="{{ filament()->getLoginUrl() }}" class="mkt-btn mkt-btn--link">← Torna al login</a>
            </div>
        @else
            <div class="mkt-auth__step-icon">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#1D574B" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>
            </div>
            <h2 class="mkt-auth__form-title">Controlla la casella</h2>
            <p class="mkt-auth__form-lead">
                Se <strong style="color: var(--text-strong);">{{ $sentEmail }}</strong> è registrata,
                riceverai a breve un messaggio con il link per reimpostare la password.
            </p>
            <div class="mkt-auth__info-box">
                <strong style="color: var(--text-strong); display: block; margin-bottom: 0.4rem;">Non trovi l'email?</strong>
                Controlla la cartella spam o posta indesiderata.<br>
                Verifica di aver inserito l'indirizzo corretto.<br>
                Il link scade dopo 60 minuti.
            </div>

            <div style="display: flex; align-items: center; gap: 1.4rem; flex-wrap: wrap;">
                <button type="button" wire:click="resend" wire:loading.attr="disabled" wire:target="resend" class="mkt-btn mkt-btn--outline">
                    <span wire:loading.remove wire:target="resend">Invia di nuovo</span>
                    <span wire:loading wire:target="resend">Invio in corso…</span>
                </button>
            </div>

            <div style="margin-top: 1.6rem;">
                <a href="{{ filament()->getLoginUrl() }}" class="mkt-btn mkt-btn--link">← Torna al login</a>
            </div>
        @endif
    </div>
</div>
