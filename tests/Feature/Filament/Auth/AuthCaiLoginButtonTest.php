<?php

declare(strict_types=1);

use App\Filament\Auth\Pages\Login;
use App\Filament\Auth\Pages\RequestPasswordReset;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

test('la pagina di login mostra il separatore "oppure" e il bottone "Accedi con l\'account CAI" disattivato ma cliccabile', function (): void {
    Livewire::test(Login::class)
        ->assertSuccessful()
        ->assertSeeHtml('mkt-auth__divider')
        ->assertSee('oppure')
        ->assertSeeText("Accedi con l'account CAI", false)
        ->assertSeeHtml('aria-disabled="true"')
        ->assertDontSeeHtml('disabled>')
        ->assertSeeHtml('@click="caiInfoOpen = true"');
});

test('la modale informativa "Funzionalità non disponibile" è presente con markup accessibile e testo atteso', function (): void {
    Livewire::test(Login::class)
        ->assertSuccessful()
        ->assertSeeHtml('role="dialog"')
        ->assertSeeHtml('aria-modal="true"')
        ->assertSeeHtml('aria-labelledby="cai-info-title"')
        ->assertSee('Funzionalità non disponibile')
        ->assertSee("L'accesso con l'account CAI non è ancora disponibile.", false)
        ->assertSeeText('Chiudi');
});

test('la modale si chiude con Esc, con il backdrop, o col bottone Chiudi, e il focus torna al bottone che l\'ha aperta', function (): void {
    Livewire::test(Login::class)
        ->assertSuccessful()
        ->assertSeeHtml('@keydown.escape.window="if (caiInfoOpen) { caiInfoOpen = false; $nextTick(() => $refs.caiInfoTrigger.focus()); }"')
        ->assertSeeHtml('mkt-auth__cai-modal-backdrop')
        ->assertSeeHtml('x-ref="caiInfoTrigger"')
        ->assertSeeHtml('x-trap.noscroll="caiInfoOpen"');
});

test('il bottone CAI e la modale informativa sono presenti solo nella pagina di login, non in recupero password', function (): void {
    Livewire::test(RequestPasswordReset::class)
        ->assertSuccessful()
        ->assertDontSeeHtml('mkt-auth__cai-btn')
        ->assertDontSeeHtml('mkt-auth__cai-modal');
});
