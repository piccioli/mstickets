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
]);

it('leaves accented italian vowels untouched', function () {
    expect(LatexEscaper::escape('così è già più però università'))
        ->toBe('così è già più però università');
});
