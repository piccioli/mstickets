<x-auth.panel tagline="Affidabilità · Competenza · Prossimità">
    <x-slot:mobile>
        <div class="mkt-auth__eyebrow">Piattaforma Servizi CAI</div>
        <div class="mkt-auth__mobile-title">Bentornato</div>
    </x-slot:mobile>

    <x-slot:desktop>
        <div class="mkt-auth__eyebrow">Piattaforma Servizi CAI</div>
        <h1 class="mkt-auth__title">Concentrati su ciò che conta: la montagna e la comunità.</h1>
        <p class="mkt-auth__lead">
            Accedi alla piattaforma di Montagna Servizi per gestire documenti, riunioni, bandi e
            progetti della tua Sezione.
        </p>
        <div class="mkt-auth__benefits">
            @foreach ([
                'Drive Standard, verbali e archivio sempre in ordine',
                'Bandi e progetti seguiti passo dopo passo',
                'Assistenza dedicata: ti rispondiamo entro 48 ore',
            ] as $benefit)
                <div class="mkt-auth__benefit">
                    <span class="mkt-auth__benefit-icon">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </span>
                    <span>{{ $benefit }}</span>
                </div>
            @endforeach
        </div>
    </x-slot:desktop>

    @if ($this->userUndertakingMultiFactorAuthentication)
        {{-- Sfida MFA (US-606, §6.7.2): stesso stato Livewire nativo di Filament\Auth\Pages\Login,
             renderizzato qui a mano perché questa view sostituisce integralmente quella di vendor
             (solo per il brand "Montagna Servizi") e altrimenti non mostrerebbe mai lo step 2. --}}
        <div class="mkt-auth__form-eyebrow">Verifica</div>
        <h2 class="mkt-auth__form-title">{{ __('filament-panels::auth/pages/login.multi_factor.heading') }}</h2>
        <p class="mkt-auth__form-lead">{{ __('filament-panels::auth/pages/login.multi_factor.subheading') }}</p>

        <form wire:submit="authenticate">
            {{ $this->multiFactorChallengeForm }}

            <button type="submit" class="mkt-btn mkt-btn--primary mkt-btn--full" style="margin-top: 1.5rem;" wire:loading.attr="disabled" wire:target="authenticate">
                <span wire:loading.remove wire:target="authenticate">Accedi</span>
                <span wire:loading wire:target="authenticate">Verifica in corso…</span>
            </button>
        </form>
    @else
        <div class="mkt-auth__form-eyebrow">Accedi</div>
        <h2 class="mkt-auth__form-title">Bentornato</h2>
        <p class="mkt-auth__form-lead">Inserisci le tue credenziali per entrare nella piattaforma.</p>

        @error('data.email')
            <div class="mkt-auth__info-box" style="border-color: var(--danger-600); background: var(--danger-100); color: var(--danger-600); margin-bottom: 1.5rem; padding: 0.9rem 1.1rem; line-height: 1.5;">
                {{ $message }}
            </div>
        @enderror

        <form wire:submit="authenticate">
            <div class="mkt-field" style="margin-bottom: 1.25rem;">
                <label for="login-email">Email</label>
                <div class="fi-input-wrp" style="display: flex; align-items: center; padding: 0.15rem 1rem;">
                    <input
                        type="email"
                        id="login-email"
                        wire:model="data.email"
                        required
                        autocomplete="email"
                        autofocus
                        placeholder="nome@sezione.cai.it"
                        style="flex: 1; border: none; outline: none; background: transparent; padding: 0.6rem 0; font: inherit;"
                    >
                </div>
            </div>

            <div class="mkt-field" x-data="{ show: false }" style="margin-bottom: 0.4rem;">
                <label for="login-password">Password</label>
                <div class="fi-input-wrp" style="display: flex; align-items: center; padding: 0.15rem 1rem;">
                    <input
                        :type="show ? 'text' : 'password'"
                        id="login-password"
                        wire:model="data.password"
                        required
                        autocomplete="current-password"
                        placeholder="La tua password"
                        style="flex: 1; border: none; outline: none; background: transparent; padding: 0.6rem 0; font: inherit;"
                    >
                </div>
                <div style="display: flex; justify-content: flex-end;">
                    <button type="button" @click="show = !show" class="mkt-btn mkt-btn--link" style="margin-top: 0.4rem;">
                        <span x-text="show ? 'Nascondi password' : 'Mostra password'"></span>
                    </button>
                </div>
            </div>

            <div class="mkt-auth__row">
                <label class="mkt-auth__remember">
                    <input type="checkbox" wire:model="data.remember">
                    <span>Salva per le prossime sessioni</span>
                </label>
                <a href="{{ filament()->getRequestPasswordResetUrl() }}" class="mkt-btn mkt-btn--link">
                    Recupera password
                </a>
            </div>

            <button type="submit" class="mkt-btn mkt-btn--primary mkt-btn--full" wire:loading.attr="disabled" wire:target="authenticate">
                <span wire:loading.remove wire:target="authenticate">Accedi</span>
                <span wire:loading wire:target="authenticate">Accesso in corso…</span>
            </button>
        </form>

        <div class="mkt-auth__divider"><span>oppure</span></div>

        <div x-data="{ caiInfoOpen: false }">
            <button
                type="button"
                x-ref="caiInfoTrigger"
                class="mkt-btn mkt-btn--outline mkt-btn--full mkt-auth__cai-btn"
                aria-disabled="true"
                @click="caiInfoOpen = true"
            >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m2 6 10 7 10-7"></path></svg>
                Accedi con l'account CAI
            </button>

            <div
                x-show="caiInfoOpen"
                style="display: none;"
                class="mkt-auth__cai-modal"
                @keydown.escape.window="if (caiInfoOpen) { caiInfoOpen = false; $nextTick(() => $refs.caiInfoTrigger.focus()); }"
            >
                <div
                    class="mkt-auth__cai-modal-backdrop"
                    @click="caiInfoOpen = false; $nextTick(() => $refs.caiInfoTrigger.focus())"
                ></div>
                <div
                    class="mkt-auth__cai-modal-dialog"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="cai-info-title"
                    x-trap.noscroll="caiInfoOpen"
                >
                    <h2 id="cai-info-title" class="mkt-auth__cai-modal-title">Funzionalità non disponibile</h2>
                    <p class="mkt-auth__cai-modal-body">
                        L'accesso con l'account CAI non è ancora disponibile. Continua ad utilizzare email e
                        password per accedere.
                    </p>
                    <button
                        type="button"
                        class="mkt-btn mkt-btn--primary"
                        @click="caiInfoOpen = false; $nextTick(() => $refs.caiInfoTrigger.focus())"
                    >
                        Chiudi
                    </button>
                </div>
            </div>
        </div>

        <div class="mkt-auth__footer-note">
            La tua Sezione non ha ancora un accesso?
            <a href="https://www.montagnaservizi.com/contatti" target="_blank">Contattaci →</a>
        </div>
    @endif
</x-auth.panel>
