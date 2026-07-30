<x-auth.panel compact tagline="Serve aiuto? Ti rispondiamo entro 48 ore." x-data="{
    pw1: '',
    pw2: '',
    get len() { return this.pw1.length >= 8 },
    get upper() { return /[A-Z]/.test(this.pw1) },
    get lower() { return /[a-z]/.test(this.pw1) },
    get num() { return /[0-9]/.test(this.pw1) },
    get score() { return (this.len ? 1 : 0) + (this.upper ? 1 : 0) + (this.lower ? 1 : 0) + (this.num ? 1 : 0) },
    get mismatch() { return this.pw2.length > 0 && this.pw1 !== this.pw2 },
}">
    <x-slot:mobile>
        <div class="mkt-auth__mobile-title">Nuova password</div>
    </x-slot:mobile>

    <x-slot:desktop>
        <div class="mkt-auth__eyebrow">Accesso sicuro</div>
        <h1 class="mkt-auth__title">Ti rimettiamo in cammino in pochi passaggi.</h1>
        <p class="mkt-auth__lead">
            Scegli una password sicura: la userai per tutti i servizi della piattaforma.
        </p>
    </x-slot:desktop>

    <div class="mkt-auth__steps">
        @foreach (['Richiesta', 'Email inviata', 'Nuova password'] as $i => $label)
            @php $n = $i + 1; @endphp
            <span class="mkt-auth__step-dot @if($n === 3) mkt-auth__step-dot--on @else mkt-auth__step-dot--done @endif">{{ $n === 3 ? 3 : '✓' }}</span>
            <span class="mkt-auth__step-label @if($n === 3) mkt-auth__step-label--active @endif">{{ $label }}</span>
            @unless ($n === 3)
                <span class="mkt-auth__step-sep"></span>
            @endunless
        @endforeach
    </div>

    <div class="mkt-auth__form-eyebrow">Nuova password</div>
    <h2 class="mkt-auth__form-title">Imposta una nuova password</h2>
    <p class="mkt-auth__form-lead">Scegli una password sicura: la userai per tutti i servizi della piattaforma.</p>

    <form wire:submit="resetPassword">
        <div class="mkt-field" style="margin-bottom: 0.2rem;">
            <label for="reset-password-1">Nuova password</label>
            <div class="fi-input-wrp" style="display: flex; align-items: center; padding: 0.15rem 1rem;">
                <input
                    type="password"
                    id="reset-password-1"
                    wire:model="password"
                    x-on:input="pw1 = $event.target.value"
                    required
                    autocomplete="new-password"
                    placeholder="Nuova password"
                    style="flex: 1; border: none; outline: none; background: transparent; padding: 0.6rem 0; font: inherit;"
                >
            </div>
        </div>

        <div class="mkt-strength">
            <div class="mkt-strength__bars">
                <span class="mkt-strength__bar" :class="score >= 1 ? (score <= 1 ? 'mkt-strength__bar--weak' : score <= 3 ? 'mkt-strength__bar--medium' : 'mkt-strength__bar--strong') : ''"></span>
                <span class="mkt-strength__bar" :class="score >= 2 ? (score <= 3 ? 'mkt-strength__bar--medium' : 'mkt-strength__bar--strong') : ''"></span>
                <span class="mkt-strength__bar" :class="score >= 4 ? 'mkt-strength__bar--strong' : ''"></span>
            </div>
            <span class="mkt-strength__label" x-text="!pw1 ? 'Sicurezza' : (score <= 1 ? 'Debole' : score <= 3 ? 'Media' : 'Sicura')"></span>
        </div>

        @error('password')
            <div class="mkt-auth__info-box" style="border-color: var(--danger-600); background: var(--danger-100); color: var(--danger-600); padding: 0.7rem 1rem; margin-bottom: 1rem;">
                {{ $message }}
            </div>
        @enderror

        <div class="mkt-field" style="margin-bottom: 0.4rem;">
            <label for="reset-password-2">Conferma password</label>
            <div class="fi-input-wrp" style="display: flex; align-items: center; padding: 0.15rem 1rem;">
                <input
                    type="password"
                    id="reset-password-2"
                    wire:model="passwordConfirmation"
                    x-on:input="pw2 = $event.target.value"
                    required
                    autocomplete="new-password"
                    placeholder="Ripeti la password"
                    style="flex: 1; border: none; outline: none; background: transparent; padding: 0.6rem 0; font: inherit;"
                >
            </div>
            <div x-show="mismatch" style="color: var(--danger-600); font-size: var(--text-sm); margin-top: 0.4rem;">
                Le due password non coincidono.
            </div>
        </div>

        <div class="mkt-rules">
            <div class="mkt-rules__item">
                <span class="mkt-rules__dot" :class="len && 'mkt-rules__dot--ok'" x-text="len ? '✓' : '·'"></span>
                <span :class="len ? 'mkt-rules__text--ok' : 'mkt-rules__text'">Almeno 8 caratteri</span>
            </div>
            <div class="mkt-rules__item">
                <span class="mkt-rules__dot" :class="upper && 'mkt-rules__dot--ok'" x-text="upper ? '✓' : '·'"></span>
                <span :class="upper ? 'mkt-rules__text--ok' : 'mkt-rules__text'">Una lettera maiuscola</span>
            </div>
            <div class="mkt-rules__item">
                <span class="mkt-rules__dot" :class="lower && 'mkt-rules__dot--ok'" x-text="lower ? '✓' : '·'"></span>
                <span :class="lower ? 'mkt-rules__text--ok' : 'mkt-rules__text'">Una lettera minuscola</span>
            </div>
            <div class="mkt-rules__item">
                <span class="mkt-rules__dot" :class="num && 'mkt-rules__dot--ok'" x-text="num ? '✓' : '·'"></span>
                <span :class="num ? 'mkt-rules__text--ok' : 'mkt-rules__text'">Almeno un numero</span>
            </div>
        </div>

        <button type="submit" class="mkt-btn mkt-btn--primary mkt-btn--full" wire:loading.attr="disabled" wire:target="resetPassword">
            <span wire:loading.remove wire:target="resetPassword">Salva la nuova password</span>
            <span wire:loading wire:target="resetPassword">Salvataggio in corso…</span>
        </button>
    </form>

    <div style="margin-top: 1.5rem;">
        <a href="{{ filament()->getLoginUrl() }}" class="mkt-btn mkt-btn--link">← Torna al login</a>
    </div>
</x-auth.panel>
