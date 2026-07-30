<?php

declare(strict_types=1);

use App\Support\Latex\LatexEscaper;

it('escapes latex reserved characters', function (string $input, string $expected) {
    expect(LatexEscaper::escape($input))->toBe($expected);
})->with([
    ['100% completato', '100\% completato'],
    ['A & B', 'A \& B'],
    ['ticket.update.*', 'ticket.update.*'],
    ['#hashtag', '\#hashtag'],
    ['snake_case_var', 'snake\_case\_var'],
    ['$prezzo', '\$prezzo'],
    ['{gruppo}', '\{gruppo\}'],
    ['tilde~qui', 'tilde\textasciitilde{}qui'],
    ['caret^qui', 'caret\textasciicircum{}qui'],
    ['back\\slash', 'back\textbackslash{}slash'],
]);

it('does not double-escape when a substitution introduces a backslash', function () {
    // '#' -> '\#' non deve poi vedersi ri-escapare il backslash appena introdotto.
    expect(LatexEscaper::escape('# & % _'))->toBe('\# \& \% \_');
});

it('substitutes risky unicode punctuation with latex-safe sequences', function (string $input, string $expected) {
    expect(LatexEscaper::escape($input))->toBe($expected);
})->with([
    ['gennaio—giugno', 'gennaio---giugno'],
    ['pagina 1–2', 'pagina 1--2'],
    ['A → B', 'A $\rightarrow$ B'],
    ['nessuno dei 5 ruoli ⇒ accesso negato', 'nessuno dei 5 ruoli $\Rightarrow$ accesso negato'],
    ['3 × 4', '3 $\times$ 4'],
    ['testo…', 'testo\ldots{}'],
    // Bug reale trovato nel Task 6 mandando gli 8 file di docs/collaudo/
    // attraverso l'intera pipeline: docs/collaudo/02-fase-0.md contiene
    // "Il test risulta passato (✓)" — senza questa mappatura pdflatex si
    // ferma con "Unicode character ✓ (U+2713) not set up for use with
    // LaTeX" (nessun PDF prodotto). −/≥/≤ mappati preventivamente per lo
    // stesso motivo (stessa classe di simboli matematici Unicode fuori da
    // T1/Latin-1), anche se non ancora osservati a valle nel corpus reale
    // al momento del fix.
    ['Il test risulta passato (✓)', 'Il test risulta passato (\checkmark{})'],
    ['valore −5', 'valore $-$5'],
    ['almeno ≥ 3', 'almeno $\geq$ 3'],
    ['al più ≤ 3', 'al più $\leq$ 3'],
]);

it('leaves accented italian vowels untouched', function () {
    expect(LatexEscaper::escape('così è già più però università'))
        ->toBe('così è già più però università');
});
