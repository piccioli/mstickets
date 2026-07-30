# Motore PDF di collaudo su LaTeX (v0.3.2) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Sostituire il motore di generazione del PDF di collaudo (`collaudo:generate`), oggi basato su dompdf/HTML, con un motore basato su pdfLaTeX che usa la classe `montagnaservizi.cls` (carta intestata ufficiale Montagna Servizi) importata dal progetto Design `Template LaTeX Montagna Servizi`, per entrambe le varianti generate (PDF sintetico da manifest PHP, PDF dettagliato da manuale Markdown).

**Architecture:** Le viste Blade esistenti (`resources/views/pdf/*.blade.php`) vengono sostituite da viste che emettono sorgente LaTeX (`resources/views/latex/*.tex.blade.php`), renderizzate a stringa e compilate con un nuovo `App\Support\Latex\LatexPdfCompiler` che shell-a `pdflatex` due volte in una directory temporanea contenente una copia di `montagnaservizi.cls` e del logo. Tutto il testo dinamico passa da `App\Support\Latex\LatexEscaper` prima di finire nel `.tex`. Il manuale Markdown viene convertito riga per riga da un nuovo `App\Support\Latex\MarkdownToLatexConverter`, scritto su misura per il sottoinsieme di sintassi Markdown realmente usato nei file di `docs/collaudo/` (verificato con analisi statica dei file sorgente, vedi §"Sottoinsieme Markdown supportato").

**Tech Stack:** PHP 8.4/Laravel 13 (Blade, `Illuminate\Support\Facades\Process`), pdfLaTeX/TeX Live (Alpine `apk`), Pest 4 per i test.

## Global Constraints

- PHP `declare(strict_types=1);` in ogni nuovo file (convenzione di progetto).
- Nessuna business logic in hook Eloquent — non applicabile qui (nessun model coinvolto), ma le nuove classi vanno in `app/Support/Latex/` (convenzione esistente per codice di supporto trasversale, vedi `App\Support\Doctor`, `App\Support\DesignTokens`).
- `composer run analyse` (Larastan livello 6, `--memory-limit=1G`) e `composer run lint` (Pint) devono passare su tutto il codice nuovo.
- TeX Live va installato **solo** in `docker/php/Dockerfile` (stack di sviluppo) e nella CI (`ubuntu-latest`, job `quality`) — **non** in `docker/uat/Dockerfile` (immagine di produzione, `collaudo:generate` non gira mai lì, confermato: nessun riferimento nel deploy).
- Pacchetti Alpine per TeX Live, **verificati funzionanti in questa sessione** con `docker build` reale su `php:8.4-fpm-alpine` (Alpine 3.24): `texlive texmf-dist-latex texmf-dist-latexrecommended texmf-dist-latexextra texmf-dist-fontsrecommended texmf-dist-fontsextra texmf-dist-langitalian texmf-dist-plaingeneric texmf-dist-pictures poppler-utils`. Non improvvisare un elenco diverso senza ri-verificare con una build reale.
- `barryvdh/laravel-dompdf` non è usato da nessun'altra parte dell'app (verificato con grep su `app/` e `resources/views/`): a fine lavoro va rimosso da `composer.json`/`composer.lock`, non lasciato come dipendenza morta.

## Sottoinsieme Markdown supportato (verificato per conteggio reale sui file in `docs/collaudo/`)

Analisi statica di tutti gli 8 file (`README.md`, `00-istruzioni-generali.md`, `01-matrice-tracciabilita.md`, `02-fase-0.md` 213KB, `03-fase-1.md` 302KB, `04-fase-1a.md`, `05-registro-esiti.md`, `06-verbale-collaudo.md`):

| Costrutto | Presente? | Note |
|---|---|---|
| Header `#`/`##`/`###` | Sì (mai `####`) | → `\section`/`\subsection`/`\subsubsection` |
| Paragrafi | Sì | testo semplice, escaping + inline |
| Grassetto `**testo**` | Sì (molto usato) | → `\textbf{}` |
| Corsivo `*t*`/`_t_` singolo | **No** (tutti i match sono falsi positivi dentro inline-code, es. `` `ticket.update.*` ``) | non implementato — evita ambiguità con glob pattern nel codice inline |
| Inline code `` `x` `` | Sì (fino a 1451 occorrenze in un file) | → `\texttt{}`, contenuto passato da `LatexEscaper` |
| Code fence ```` ``` ```` | Sì (max 8 in un file) | → `lstlisting` (stile `ms` già nella classe) |
| Liste puntate `- ` | Sì | → `itemize` |
| Liste numerate `1. ` | Sì (poche, max 7) | → `enumerate` |
| Liste annidate | Sì, solo in `README.md` (3 casi) | un livello di indentazione, itemize annidato |
| Checkbox `- [ ]` | Sì, solo in `06-verbale-collaudo.md` (3 casi) | → itemize con label `$\square$` |
| Blockquote `> ` | Sì (poche per file) | → `quote` (kernel LaTeX, nessun pacchetto extra) |
| Hr `---` | Sì (fino a 71 in un file) | → `\msseparatore` |
| Tabelle pipe | Sì (fino a 528 righe in un file) | → `\mdtabella` (nuovo, xltabular, pagina multipla) |
| Allineamento colonna `--:` | Sì (solo destra, mai `:--:`) | supportato; centro non necessario ma innocuo da supportare |
| Link `[testo](url)` | Sì | interni (`.md`) → solo testo del link (stesso documento combinato); esterni (`http`) → `\href` |
| `<br>`, pipe escapata `\|` nelle celle | **No** | non implementato |
| Caratteri Unicode a rischio | `—`(dash), `→`, `⇒`, `×`, `…` | gestiti da `LatexEscaper` (sostituzione, non inputenc) |
| Vocali accentate italiane | Sì (frequenti) | nessun problema, `T1`+`utf8` le gestiscono nativamente |

## Bug scoperti e corretti nella classe `montagnaservizi.cls` durante la verifica

La classe importata dal progetto Design **non compilava**: 6 costrutti su un totale di 13 fallivano. Root cause diagnosticata e corretta empiricamente (build Docker reale, non supposizioni):

1. **`mstabella`, `storicorevisioni`, `presenze`, `azioni`, `voci`** (5 costrutti): erano `\newenvironment` che aprono `\begin{tabularx}` nel codice di apertura e lo chiudono con `\end{tabularx}` nel codice di chiusura. `tabularx` legge l'intero corpo della tabella come argomento verbatim fino al primo `\end{tabularx}` **letterale** che incontra nel flusso di token (`\TX@get@body`). Quando apertura e chiusura vengono da due macro diverse, quel `\end{tabularx}` non è mai letterale nello stream (arriva solo per espansione successiva) e la scansione consuma tutto il resto del documento fino a EOF → `Runaway argument? File ended while scanning use of \TX@get@body`. **Fix**: convertiti da `\newenvironment` a `\newcommand` con il corpo della tabella come ultimo argomento obbligatorio, cosicché `\begin{tabularx}...corpo...\end{tabularx}` arrivi nello stream in un solo colpo dalla stessa espansione.
2. **`firme`**: un `\dimexpr` che referenzia `\textwidth` scritto direttamente dentro una colonna `p{...}`, quando quella `tabular` vive dentro una `minipage` preceduta da altre `tabularx`/`minipage` nello stesso documento, produce `Illegal unit of measure (pt inserted)` (i registri temporanei di `tabularx` restano "in volo" e interferiscono). **Fix**: sostituite le colonne calcolate a mano con colonne elastiche `X` di `tabularx` (stesso meccanismo robusto già usato altrove), eliminando il `\dimexpr` live; convertita anch'essa da environment a comando con corpo come argomento (stessa causa root del punto 1, essendo ora basata su `tabularx`).
3. **Nuovo `mdtabella`** (non esisteva nel progetto Design, introdotto per questo piano): serve una tabella che si spezzi su più pagine con colonne elastiche, per le tabelle molto lunghe del manuale Markdown. `longtable` puro non supporta colonne `X`; serve `xltabular` (longtable + tabularx). `xltabular` eredita lo stesso vincolo del punto 1 (corpo verbatim) — quindi anche `mdtabella` è un `\newcommand` a 3 argomenti (colonne, intestazione, corpo), mai un environment.

Verificato con build Docker reale (`php:8.4-fpm-alpine` + pacchetti sopra) e compilazione doppia (`pdflatex -interaction=nonstopmode -halt-on-error`, due passate) di un documento che esercita tutti e 13 i costrutti della classe, inclusa una tabella da 89 righe che si spezza su 3 pagine con intestazione ripetuta e caratteri `\%`/`\_` escapati correttamente nel testo estratto (`pdftotext -layout`).

Il file corretto e verificato è disponibile in
`/private/tmp/claude-501/-Users-alessiopiccioli-Documents-LAVORO-MS-SOFTWARE-mstickets/3f7e8a73-e51c-4760-aabb-e6a6406e2658/scratchpad/latex-design/montagnaservizi.cls`
(e il logo in `.../montagna-servizi-logo.png` nella stessa cartella) — **usare questi file**, non ri-scaricare dal progetto Design (che ha ancora la versione rotta).

---

### Task 1: Vendorizzare la classe LaTeX e il logo nel repository

**Files:**
- Create: `orchestrator/resources/latex/montagnaservizi.cls`
- Create: `orchestrator/resources/latex/assets/montagna-servizi-logo.png`
- Create: `orchestrator/resources/latex/README.md`

**Interfaces:**
- Produces: `resources/latex/montagnaservizi.cls` — classe LaTeX richiesta da tutti i `.tex.blade.php` futuri via `\documentclass[italiano]{montagnaservizi}`. `resources/latex/assets/montagna-servizi-logo.png` — logo referenziato da `\mslogofile` (default `montagna-servizi-logo`, cerca `.png` in `assets/` per via di `\graphicspath`).

- [ ] **Step 1: Copiare il file `.cls` corretto e verificato**

Copia **esattamente** il contenuto del file verificato in questa sessione (fix ai 6 costrutti descritti sopra) in `orchestrator/resources/latex/montagnaservizi.cls`:

```bash
mkdir -p orchestrator/resources/latex/assets
cp "/private/tmp/claude-501/-Users-alessiopiccioli-Documents-LAVORO-MS-SOFTWARE-mstickets/3f7e8a73-e51c-4760-aabb-e6a6406e2658/scratchpad/latex-design/montagnaservizi.cls" \
   orchestrator/resources/latex/montagnaservizi.cls
cp "/private/tmp/claude-501/-Users-alessiopiccioli-Documents-LAVORO-MS-SOFTWARE-mstickets/3f7e8a73-e51c-4760-aabb-e6a6406e2658/scratchpad/latex-design/montagna-servizi-logo.png" \
   orchestrator/resources/latex/assets/montagna-servizi-logo.png
```

Se lo scratchpad della sessione precedente non è più disponibile: il file va ri-costruito applicando manualmente le 3 modifiche descritte in "Bug scoperti e corretti" sopra al file originale del progetto Design (`montagnaservizi.cls`, `assets/montagna-servizi-logo.png`), poi ri-verificato con lo Step 2 di questo task prima di proseguire — **non** saltare la riverifica.

- [ ] **Step 2: Verificare che la classe compili da sola, isolata dal resto del repo**

```bash
cd orchestrator/resources/latex
cat > /tmp/smoke.tex << 'EOF'
\documentclass[italiano]{montagnaservizi}
\titolodoc{Smoke test}
\begin{document}
\copertina
\mdtabella{@{}Xp{20mm}@{}}{\thc{A} & \thc{B}}{Riga & 1 \\}
\end{document}
EOF
cp /tmp/smoke.tex .
docker run --rm -v "$(pwd):/work" -w /work php:8.4-fpm-alpine sh -c "
  apk add --no-cache texlive texmf-dist-latex texmf-dist-latexrecommended texmf-dist-latexextra texmf-dist-fontsrecommended texmf-dist-fontsextra texmf-dist-langitalian texmf-dist-plaingeneric texmf-dist-pictures poppler-utils >/dev/null 2>&1
  pdflatex -interaction=nonstopmode -halt-on-error smoke.tex && pdflatex -interaction=nonstopmode -halt-on-error smoke.tex
"
ls smoke.pdf && rm -f smoke.* 
```

Expected: `smoke.pdf` esiste, nessun `!` nel log.

- [ ] **Step 3: Scrivere `README.md` di provenienza**

```markdown
# Classe LaTeX Montagna Servizi

Importata dal progetto Claude Design "Template LaTeX Montagna Servizi"
(https://claude.ai/design/p/7e82d898-e948-4dd2-905f-c557482245ca) il 30/07/2026,
poi corretta: la versione originale non compilava su 6 dei 13 costrutti
(vedi CLAUDE.md, sezione "Motore PDF di collaudo — LaTeX", per i dettagli
dei bug e dei fix). Non ri-sincronizzare da quel progetto senza riapplicare
i fix o verificare che siano stati applicati anche lì.

Usata da `App\Support\Latex\LatexPdfCompiler` per compilare i PDF di
collaudo generati da `php artisan collaudo:generate`. Motore: pdfLaTeX
(TeX Live), non installato di default: vedi `docker/php/Dockerfile`.
```

- [ ] **Step 4: Commit**

```bash
cd orchestrator
git add resources/latex/
git commit -m "feat: vendorizza la classe LaTeX montagnaservizi (con fix a 6 costrutti rotti)"
```

---

### Task 2: `LatexEscaper` — escaping di testo dinamico per LaTeX

**Files:**
- Create: `orchestrator/app/Support/Latex/LatexEscaper.php`
- Test: `orchestrator/tests/Unit/Support/Latex/LatexEscaperTest.php`

**Interfaces:**
- Produces: `App\Support\Latex\LatexEscaper::escape(string $text): string` — usata da ogni altro task che inserisce testo dinamico in un `.tex` (manifest PHP, contenuto Markdown convertito).

- [ ] **Step 1: Scrivere il test (fallente)**

```php
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
```

- [ ] **Step 2: Eseguire il test e verificare che fallisca**

Run: `cd orchestrator && vendor/bin/pest tests/Unit/Support/Latex/LatexEscaperTest.php`
Expected: FAIL — classe `LatexEscaper` non esiste.

- [ ] **Step 3: Implementare**

```php
<?php

declare(strict_types=1);

namespace App\Support\Latex;

final class LatexEscaper
{
    /**
     * L'ordine di questa mappa non ha effetto sul risultato: strtr() con un
     * array fa una singola sostituzione simultanea (i valori sostituiti non
     * vengono ri-scansionati), a differenza di str_replace() concatenati che
     * ri-escaperebbero i backslash appena inseriti da un escape precedente.
     *
     * @var array<string, string>
     */
    private const MAP = [
        '\\' => '\textbackslash{}',
        '%' => '\%',
        '&' => '\&',
        '#' => '\#',
        '_' => '\_',
        '$' => '\$',
        '{' => '\{',
        '}' => '\}',
        '~' => '\textasciitilde{}',
        '^' => '\textasciicircum{}',
        '—' => '---',
        '–' => '--',
        '→' => '$\rightarrow$',
        '⇒' => '$\Rightarrow$',
        '×' => '$\times$',
        '…' => '\ldots{}',
    ];

    public static function escape(string $text): string
    {
        return strtr($text, self::MAP);
    }
}
```

- [ ] **Step 4: Eseguire il test e verificare che passi**

Run: `cd orchestrator && vendor/bin/pest tests/Unit/Support/Latex/LatexEscaperTest.php`
Expected: PASS (tutti i case, incluso il non-double-escaping — `strtr` lo garantisce strutturalmente).

- [ ] **Step 5: Lint + analisi statica**

Run: `cd orchestrator && vendor/bin/pint app/Support/Latex/LatexEscaper.php tests/Unit/Support/Latex/LatexEscaperTest.php && vendor/bin/phpstan analyse --memory-limit=1G`
Expected: nessun errore.

- [ ] **Step 6: Commit**

```bash
cd orchestrator
git add app/Support/Latex/LatexEscaper.php tests/Unit/Support/Latex/LatexEscaperTest.php
git commit -m "feat: aggiunge LatexEscaper per il testo dinamico nei documenti LaTeX di collaudo"
```

---

### Task 3: `LatexPdfCompiler` — compilazione di un sorgente `.tex` in PDF

**Files:**
- Create: `orchestrator/app/Support/Latex/LatexPdfCompiler.php`
- Test: `orchestrator/tests/Feature/Support/Latex/LatexPdfCompilerTest.php`

**Interfaces:**
- Consumes: nessuna dipendenza da task precedenti (usa solo `resources/latex/montagnaservizi.cls`/`assets/` prodotti dal Task 1, tramite `resource_path('latex/...')`).
- Produces: `App\Support\Latex\LatexPdfCompiler::compile(string $texSource): string` — riceve il sorgente LaTeX completo (già renderizzato da una vista Blade), restituisce il path assoluto a un file PDF temporaneo compilato. Usata da `CollaudoGenerateCommand` (Task 4/6).

- [ ] **Step 1: Scrivere il test (fallente)**

Questo test richiede `pdflatex` disponibile nell'ambiente in cui gira (host di sviluppo con TeX Live, o container/CI con il Dockerfile aggiornato dal Task 7/8) — è un test di integrazione reale, non un mock, coerente con la convenzione di progetto "verificare per davvero", vedi CLAUDE.md.

```php
<?php

declare(strict_types=1);

use App\Support\Latex\LatexPdfCompiler;
use Illuminate\Support\Facades\Process;

it('compiles a minimal valid latex document into a real pdf', function () {
    $tex = <<<'TEX'
    \documentclass{article}
    \begin{document}
    Documento di prova.
    \end{document}
    TEX;

    $path = app(LatexPdfCompiler::class)->compile($tex);

    expect($path)->toBeFile();
    expect(file_get_contents($path))->toStartWith('%PDF');
});

it('compiles a document using the montagnaservizi class and the bundled logo', function () {
    $tex = <<<'TEX'
    \documentclass[italiano]{montagnaservizi}
    \titolodoc{Documento di prova}
    \begin{document}
    \copertina
    Contenuto.
    \end{document}
    TEX;

    $path = app(LatexPdfCompiler::class)->compile($tex);

    expect($path)->toBeFile();
    expect(file_get_contents($path))->toStartWith('%PDF');
});

it('throws with the compiler log tail when the source is invalid', function () {
    $tex = <<<'TEX'
    \documentclass{article}
    \begin{document}
    \undefinedcommandthatdoesnotexist
    \end{document}
    TEX;

    app(LatexPdfCompiler::class)->compile($tex);
})->throws(RuntimeException::class);

it('does not leave temporary compilation directories behind', function () {
    $before = glob(sys_get_temp_dir().'/ms-latex-*') ?: [];

    app(LatexPdfCompiler::class)->compile(<<<'TEX'
    \documentclass{article}
    \begin{document}
    X
    \end{document}
    TEX);

    $after = glob(sys_get_temp_dir().'/ms-latex-*') ?: [];

    expect($after)->toHaveCount(count($before));
});
```

- [ ] **Step 2: Eseguire il test e verificare che fallisca**

Run: `cd orchestrator && vendor/bin/pest tests/Feature/Support/Latex/LatexPdfCompilerTest.php`
Expected: FAIL — classe `LatexPdfCompiler` non esiste.

- [ ] **Step 3: Implementare**

```php
<?php

declare(strict_types=1);

namespace App\Support\Latex;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;

final class LatexPdfCompiler
{
    /**
     * Compila un sorgente LaTeX completo in PDF, in una directory temporanea
     * isolata che contiene una copia di montagnaservizi.cls e degli asset
     * (logo): pdflatex risolve \documentclass/\includegraphics solo
     * relativamente alla propria working directory. Compila due volte
     * (indice, riferimenti, numero di pagina — footer della classe usa
     * \pageref{LastPage}), coerente con la nota "Compilazione" del README
     * della classe stessa.
     *
     * @return string path assoluto al PDF compilato, ancora nella directory
     *                 temporanea: il chiamante è responsabile di spostarlo/
     *                 leggerne il contenuto prima che un futuro cleanup lo
     *                 rimuova (questa classe non fa cleanup automatico dei
     *                 PDF con successo, solo delle directory in caso di
     *                 errore, per permettere l'ispezione del sorgente/log
     *                 generato in caso di debug manuale).
     */
    public function compile(string $texSource): string
    {
        $workDir = sys_get_temp_dir().'/ms-latex-'.Str::random(16);
        File::makeDirectory($workDir, recursive: true);

        File::copy(resource_path('latex/montagnaservizi.cls'), $workDir.'/montagnaservizi.cls');
        File::copyDirectory(resource_path('latex/assets'), $workDir.'/assets');
        File::put($workDir.'/document.tex', $texSource);

        try {
            $this->runPdflatex($workDir);
            $this->runPdflatex($workDir);
        } catch (RuntimeException $e) {
            File::deleteDirectory($workDir);
            throw $e;
        }

        $pdfPath = $workDir.'/document.pdf';

        if (! File::exists($pdfPath)) {
            $log = File::exists($workDir.'/document.log')
                ? File::get($workDir.'/document.log')
                : '(nessun file di log prodotto)';
            File::deleteDirectory($workDir);

            throw new RuntimeException(
                "pdflatex non ha prodotto un PDF. Coda del log:\n".self::tail($log),
            );
        }

        return $pdfPath;
    }

    private function runPdflatex(string $workDir): void
    {
        $result = Process::path($workDir)
            ->timeout(120)
            ->run([
                'pdflatex',
                '-interaction=nonstopmode',
                '-halt-on-error',
                'document.tex',
            ]);

        if ($result->failed()) {
            $log = File::exists($workDir.'/document.log')
                ? File::get($workDir.'/document.log')
                : $result->output();

            throw new RuntimeException(
                "pdflatex ha fallito la compilazione. Coda del log:\n".self::tail($log),
            );
        }
    }

    private static function tail(string $text, int $lines = 40): string
    {
        $allLines = explode("\n", $text);

        return implode("\n", array_slice($allLines, -$lines));
    }
}
```

- [ ] **Step 4: Eseguire il test e verificare che passi**

Run: `cd orchestrator && vendor/bin/pest tests/Feature/Support/Latex/LatexPdfCompilerTest.php`
Expected: PASS. Se fallisce con "pdflatex: command not found", installare prima TeX Live sull'host di sviluppo (fuori scope di questo task se si sta eseguendo il piano da un ambiente senza TeX Live — vedi Task 7 per l'ambiente Docker, che è l'ambiente di riferimento per questo comando) oppure eseguire il test dentro il container `app` dopo il Task 7.

- [ ] **Step 5: Lint + analisi statica**

Run: `cd orchestrator && vendor/bin/pint app/Support/Latex/LatexPdfCompiler.php tests/Feature/Support/Latex/LatexPdfCompilerTest.php && vendor/bin/phpstan analyse --memory-limit=1G`
Expected: nessun errore.

- [ ] **Step 6: Commit**

```bash
cd orchestrator
git add app/Support/Latex/LatexPdfCompiler.php tests/Feature/Support/Latex/LatexPdfCompilerTest.php
git commit -m "feat: aggiunge LatexPdfCompiler per compilare sorgenti LaTeX in PDF via pdflatex"
```

---

### Task 4: Vista LaTeX + wiring per il PDF sintetico (`collaudo.blade.php` → LaTeX)

**Files:**
- Create: `orchestrator/resources/views/latex/collaudo.tex.blade.php`
- Modify: `orchestrator/app/Console/Commands/CollaudoGenerateCommand.php:125-133` (metodo `buildPdf`)
- Test: `orchestrator/tests/Feature/Console/CollaudoGenerateCommandTest.php` (esistente, da estendere)
- Delete: `orchestrator/resources/views/pdf/collaudo.blade.php`
- Delete: `orchestrator/resources/views/pdf/partials/collaudo-copertina.blade.php`

**Interfaces:**
- Consumes: `App\Support\Latex\LatexEscaper::escape()` (Task 2), `App\Support\Latex\LatexPdfCompiler::compile()` (Task 3).
- Produces: nessuna nuova interfaccia pubblica (il metodo `buildPdf(string $fase, array $manifest): string` di `CollaudoGenerateCommand` mantiene la stessa firma, usata dal Task 6 e dai test esistenti).

- [ ] **Step 1: Estendere il test esistente con un caso di escaping**

Aggiungere a `tests/Feature/Console/CollaudoGenerateCommandTest.php` (accanto al test già presente, senza modificarlo):

```php
it('genera un pdf di collaudo correttamente escapato quando il manifest contiene caratteri speciali latex', function () {
    $manifest = [
        'fase' => '0-1',
        'titolo' => 'Fase 0 + Fase 1',
        'parte_1' => [
            'app_url' => 'https://ticket-uat.montagnaservizi.com',
            'mailpit_url' => 'https://mailpit-ticket-uat.montagnaservizi.com',
            'credenziali' => [
                ['ruolo' => 'Admin & Root', 'email' => 'admin_100%@example.test', 'password' => 'pa$$word'],
            ],
        ],
        'topics' => [
            [
                'titolo' => 'Permessi ticket.update.* e #priorità',
                'test' => [
                    [
                        'id' => 'F0-01',
                        'descrizione' => 'Verifica accesso — 100% completo',
                        'test_automatico' => 'tests/Feature/X.php::it works',
                    ],
                ],
            ],
        ],
    ];

    $path = app(CollaudoGenerateCommand::class)->buildPdf('0-1', $manifest);

    expect($path)->toBeFile();
    expect(file_get_contents($path))->toStartWith('%PDF');

    $text = shell_exec('pdftotext -layout '.escapeshellarg($path).' -');

    expect($text)->toContain('Admin & Root');
    expect($text)->toContain('admin_100%@example.test');
    expect($text)->toContain('ticket.update.* e #priorità');
});
```

- [ ] **Step 2: Eseguire i test e verificare che il nuovo fallisca (il vecchio passa ancora, dompdf non toccato)**

Run: `cd orchestrator && vendor/bin/pest tests/Feature/Console/CollaudoGenerateCommandTest.php`
Expected: il test esistente passa (dompdf), il nuovo fallisce (dompdf non ha `pdftotext` come requisito ma soprattutto stiamo per cambiare motore: se vuoi vedere il fallimento "vero" del nuovo requisito, procedi comunque allo Step 3 — il punto di questo step è solo confermare che l'esistente non sia già rotto prima di toccare nulla).

- [ ] **Step 3: Scrivere la vista LaTeX**

`resources/views/latex/collaudo.tex.blade.php`:

```blade
\documentclass[italiano]{montagnaservizi}
\titolodoc{Documento di collaudo{{ '' }}}
\sottotitolo{{{ '{' }}{{ $titolo }}{{ '}' }}}
\begin{document}

\bloccotitolo

\section*{Parte 1 --- Come eseguire il collaudo}

Applicazione: \msurl{% raw %}{{% endraw %}{{ $appUrl }}{% raw %}}{% endraw %}

Mailpit (email di test): \msurl{% raw %}{{% endraw %}{{ $mailpitUrl }}{% raw %}}{% endraw %}

\mdtabella{@{}p{30mm}Xp{30mm}@{}}{\thc{Ruolo} & \thc{Email} & \thc{Password}}{%
@foreach ($credenziali as $cred)
{{ $cred['ruolo'] }} & \texttt{{{ '{' }}{{ $cred['email'] }}{{ '}' }}} & \texttt{{{ '{' }}{{ $cred['password'] }}{{ '}' }}} \\
@endforeach
}

\subsection*{Come accedere a Mailpit}

Le email inviate dall'ambiente UAT non escono realmente: sono intercettate da
Mailpit, raggiungibile all'indirizzo sopra con autenticazione HTTP
(utente/password forniti separatamente dal team, non stampati in questo
documento per non esporli insieme all'URL pubblico).

\subsection*{Come segnalare un problema}

Per ogni test fallito, annotare l'ID del test (es. F1-03), una descrizione di
cosa è successo invece del comportamento atteso, e se possibile uno
screenshot.

\subsection*{Indice degli argomenti}

\begin{enumerate}
@foreach ($topics as $topic)
  \item {{ $topic['titolo'] }} ({{ count($topic['test']) }} test)
@endforeach
\end{enumerate}

\end{document}
```

Nota: `{{ '{' }}`/`{{ '}' }}` servono perché Blade userebbe `{{ }}` per il proprio output — le graffe letterali LaTeX che DEVONO restare tali (es. `\msurl{...}`) vanno scritte intorno all'espressione Blade, non dentro un singolo `{{ }}` che le consumerebbe. In alternativa, più leggibile: costruire le stringhe già complete lato PHP nel Command (Step 4) e passarle alla vista già pronte per l'interpolazione semplice `{{ $stringaGiaLatex }}` — **preferire questa strada** per evitare la ginnastica di escaping graffe in Blade; il template sopra è illustrativo del contenuto, il Command deve pre-comporre le celle già come stringhe LaTeX complete (vedi Step 4).

- [ ] **Step 4: Riscrivere `buildPdf` per pre-comporre i dati ed usare il compiler LaTeX**

Sostituire in `app/Console/Commands/CollaudoGenerateCommand.php` (righe 122-133):

```php
    /**
     * @param  array<string, mixed>  $manifest
     */
    public function buildPdf(string $fase, array $manifest): string
    {
        $credenziali = array_map(
            static fn (array $cred): array => [
                'ruolo' => LatexEscaper::escape($cred['ruolo']),
                'email' => LatexEscaper::escape($cred['email']),
                'password' => LatexEscaper::escape($cred['password']),
            ],
            $manifest['parte_1']['credenziali'],
        );

        $topics = array_map(
            static fn (array $topic): array => [
                'titolo' => LatexEscaper::escape($topic['titolo']),
                'test' => $topic['test'],
            ],
            $manifest['topics'],
        );

        $tex = view('latex.collaudo', [
            'titolo' => LatexEscaper::escape($manifest['titolo']),
            'appUrl' => LatexEscaper::escape($manifest['parte_1']['app_url']),
            'mailpitUrl' => LatexEscaper::escape($manifest['parte_1']['mailpit_url']),
            'credenziali' => $credenziali,
            'topics' => $topics,
        ])->render();

        $pdfPath = app(LatexPdfCompiler::class)->compile($tex);

        $filename = sprintf('collaudo-fase-%s-%s.pdf', $fase, now()->format('Ymd-His'));
        $disk = Storage::build(['driver' => 'local', 'root' => storage_path('app')]);
        $disk->put("collaudo/{$filename}", file_get_contents($pdfPath));

        return storage_path("app/collaudo/{$filename}");
    }
```

Aggiungere gli `use` necessari in cima al file:

```php
use App\Support\Latex\LatexEscaper;
use App\Support\Latex\LatexPdfCompiler;
```

Rimuovere `use Barryvdh\DomPDF\Facade\Pdf;` (non più usato da questo metodo — resterà finché `buildDetailedPdf`, Task 6, non è convertito anch'esso).

Riscrivere la vista (Step 3) evitando la ginnastica di graffe: la versione finale della vista deve limitarsi a interpolare stringhe già pronte, es.:

```blade
\documentclass[italiano]{montagnaservizi}
\titolodoc{Documento di collaudo}
\sottotitolo{{{ $titolo }}}
\begin{document}

\bloccotitolo

\section*{Parte 1 --- Come eseguire il collaudo}

Applicazione: \msurl{{{ $appUrl }}}

Mailpit (email di test): \msurl{{{ $mailpitUrl }}}

\mdtabella{@{}p{30mm}Xp{30mm}@{}}{\thc{Ruolo} & \thc{Email} & \thc{Password}}{%
@foreach ($credenziali as $cred)
{{ $cred['ruolo'] }} & \texttt{{{ $cred['email'] }}} & \texttt{{{ $cred['password'] }}} \\
@endforeach
}

\subsection*{Come accedere a Mailpit}

Le email inviate dall'ambiente UAT non escono realmente: sono intercettate da
Mailpit, raggiungibile all'indirizzo sopra con autenticazione HTTP (utente/
password forniti separatamente dal team, non stampati in questo documento
per non esporli insieme all'URL pubblico).

\subsection*{Come segnalare un problema}

Per ogni test fallito, annotare l'ID del test (es. F1-03), una descrizione di
cosa è successo invece del comportamento atteso, e se possibile uno
screenshot.

\subsection*{Indice degli argomenti}

\begin{enumerate}
@foreach ($topics as $topic)
  \item {{ $topic['titolo'] }} ({{ count($topic['test']) }} test)
@endforeach
\end{enumerate}

\end{document}
```

Nota: `\texttt{{{ $x }}}` — Blade interpreta `{{ $x }}` e le graffe attorno restano letterali LaTeX perché Blade cerca specificamente la sequenza `{{ ... }}`, non conta le graffe singole adiacenti: verificare comunque visivamente il `.tex` renderizzato (`view(...)->render()` in tinker) prima di considerare questo step completo, per escludere ambiguità di parsing Blade con `\texttt{{{` (tre graffe di fila).

- [ ] **Step 5: Eliminare le viste dompdf superate**

```bash
cd orchestrator
git rm resources/views/pdf/collaudo.blade.php resources/views/pdf/partials/collaudo-copertina.blade.php
```

- [ ] **Step 6: Eseguire i test**

Run: `cd orchestrator && vendor/bin/pest tests/Feature/Console/CollaudoGenerateCommandTest.php`
Expected: PASS per entrambi i test (richiede `pdflatex`/`pdftotext` disponibili nell'ambiente di esecuzione — vedi Task 7/8 se si esegue in CI/Docker).

- [ ] **Step 7: Lint + analisi statica**

Run: `cd orchestrator && vendor/bin/pint app/Console/Commands/CollaudoGenerateCommand.php && vendor/bin/phpstan analyse --memory-limit=1G`
Expected: nessun errore.

- [ ] **Step 8: Commit**

```bash
cd orchestrator
git add -A
git commit -m "feat: genera il PDF di collaudo sintetico con LaTeX invece di dompdf"
```

---

### Task 5: `MarkdownToLatexConverter`

**Files:**
- Create: `orchestrator/app/Support/Latex/MarkdownToLatexConverter.php`
- Test: `orchestrator/tests/Unit/Support/Latex/MarkdownToLatexConverterTest.php`

**Interfaces:**
- Consumes: `App\Support\Latex\LatexEscaper::escape()` (Task 2), per ogni segmento di testo semplice e per il contenuto delle celle di tabella/code span.
- Produces: `App\Support\Latex\MarkdownToLatexConverter::convert(string $markdown): string` — usata da `CollaudoGenerateCommand::buildDetailedPdf` (Task 6).

- [ ] **Step 1: Scrivere i test (falliscono)**

```php
<?php

declare(strict_types=1);

use App\Support\Latex\MarkdownToLatexConverter;

it('converts headers to section commands', function () {
    $out = (new MarkdownToLatexConverter)->convert(
        "# Titolo\n\n## Sotto\n\n### Sottosotto\n"
    );

    expect($out)->toContain('\section{Titolo}');
    expect($out)->toContain('\subsection{Sotto}');
    expect($out)->toContain('\subsubsection{Sottosotto}');
});

it('converts a plain paragraph and escapes latex special characters', function () {
    $out = (new MarkdownToLatexConverter)->convert("Il 100% dei test_case passa & funziona.\n");

    expect($out)->toContain('Il 100\% dei test\_case passa \& funziona.');
});

it('converts bold and inline code within a paragraph', function () {
    $out = (new MarkdownToLatexConverter)->convert(
        "**Obiettivo**\nVerifica che `ticket.update.*` funzioni.\n"
    );

    expect($out)->toContain('\textbf{Obiettivo}');
    expect($out)->toContain('\texttt{ticket.update.*}');
});

it('converts a bullet list', function () {
    $out = (new MarkdownToLatexConverter)->convert(
        "- primo elemento\n- secondo elemento\n"
    );

    expect($out)->toContain('\begin{itemize}');
    expect($out)->toContain('\item primo elemento');
    expect($out)->toContain('\item secondo elemento');
    expect($out)->toContain('\end{itemize}');
});

it('converts a numbered list', function () {
    $out = (new MarkdownToLatexConverter)->convert(
        "1. primo\n2. secondo\n"
    );

    expect($out)->toContain('\begin{enumerate}');
    expect($out)->toContain('\item primo');
    expect($out)->toContain('\end{enumerate}');
});

it('converts a checkbox list using square symbols', function () {
    $out = (new MarkdownToLatexConverter)->convert(
        "- [ ] Collaudo superato\n- [ ] Collaudo non superato\n"
    );

    expect($out)->toContain('$\square$ Collaudo superato');
    expect($out)->toContain('$\square$ Collaudo non superato');
});

it('converts a blockquote to a quote environment, dropping internal markdown links', function () {
    $out = (new MarkdownToLatexConverter)->convert(
        "> Torna a [`README.md`](README.md) · vedi [`00-istruzioni.md`](00-istruzioni.md)\n"
    );

    expect($out)->toContain('\begin{quote}');
    expect($out)->toContain('\texttt{README.md}');
    expect($out)->not->toContain('\href');
});

it('converts an external link to href', function () {
    $out = (new MarkdownToLatexConverter)->convert(
        "Vai su [Montagna Servizi](https://montagnaservizi.com) per informazioni.\n"
    );

    expect($out)->toContain('\href{https://montagnaservizi.com}{Montagna Servizi}');
});

it('converts a horizontal rule to msseparatore', function () {
    $out = (new MarkdownToLatexConverter)->convert("Testo prima\n\n---\n\nTesto dopo\n");

    expect($out)->toContain('\msseparatore');
});

it('converts a fenced code block to lstlisting', function () {
    $out = (new MarkdownToLatexConverter)->convert(
        "```php\nRoute::get('/x', fn () => 1);\n```\n"
    );

    expect($out)->toContain('\begin{lstlisting}');
    expect($out)->toContain("Route::get('/x', fn () => 1);");
    expect($out)->toContain('\end{lstlisting}');
});

it('converts a pipe table into a mdtabella with escaped cell content', function () {
    $markdown = <<<'MD'
    | Passo | Azione | Risultato |
    |------:|--------|-----------|
    | 1 | Fai `x_y` | 100% ok |
    | 2 | Fai altro | 50% ok |
    MD;

    $out = (new MarkdownToLatexConverter)->convert($markdown);

    expect($out)->toContain('\mdtabella{');
    expect($out)->toContain('\thc{Passo}');
    expect($out)->toContain('\thc{Azione}');
    expect($out)->toContain('\thc{Risultato}');
    expect($out)->toContain('\texttt{x\_y}');
    expect($out)->toContain('100\% ok');
});

it('right-aligns a table column marked with --: in the header separator', function () {
    $markdown = <<<'MD'
    | A | B |
    |---|--:|
    | x | 1 |
    MD;

    $out = (new MarkdownToLatexConverter)->convert($markdown);

    expect($out)->toMatch('/\{@\{\}.*>\{\\\\raggedleft\\\\arraybackslash\}.*@\{\}\}/');
});

it('breaks a bold-only label line from the text that follows onto its own paragraph', function () {
    // Pattern usato in tutti i casi di test di docs/collaudo/02-fase-0.md e
    // 03-fase-1.md: "**Obiettivo**\nTesto..." con un SOLO a-capo (non riga
    // vuota). Senza un \par esplicito qui, LaTeX tratterebbe l'a-capo come
    // uno spazio (stesso comportamento CommonMark "soft break"), fondendo
    // visivamente l'etichetta in grassetto col testo — il problema che il
    // vecchio helper CommonMark-specifico separateBoldLabelsIntoOwnParagraph
    // (rimosso nel Task 6) risolveva per la pipeline dompdf.
    $out = (new MarkdownToLatexConverter)->convert("**Obiettivo**\nTesto che segue subito dopo.");

    expect($out)->toBe("\\textbf{Obiettivo}\\par\nTesto che segue subito dopo.");
});

it('does not insert a paragraph break when bold text is inline within a sentence', function () {
    $out = (new MarkdownToLatexConverter)->convert('Il **grassetto** qui è inline, non un\'etichetta.');

    expect($out)->not->toContain('\par');
});
```

- [ ] **Step 2: Eseguire i test e verificare che falliscano**

Run: `cd orchestrator && vendor/bin/pest tests/Unit/Support/Latex/MarkdownToLatexConverterTest.php`
Expected: FAIL — classe non esiste.

- [ ] **Step 3: Implementare**

```php
<?php

declare(strict_types=1);

namespace App\Support\Latex;

final class MarkdownToLatexConverter
{
    public function convert(string $markdown): string
    {
        $blocks = preg_split('/\n{2,}/', trim($markdown)) ?: [];
        $out = [];

        foreach ($blocks as $block) {
            $out[] = $this->convertBlock($block);
        }

        return implode("\n\n", array_filter($out, static fn (string $b): bool => $b !== ''));
    }

    private function convertBlock(string $block): string
    {
        $lines = explode("\n", $block);
        $first = $lines[0];

        if (preg_match('/^### (.+)$/', $first, $m)) {
            return '\subsubsection{'.$this->inline($m[1]).'}';
        }

        if (preg_match('/^## (.+)$/', $first, $m)) {
            return '\subsection{'.$this->inline($m[1]).'}';
        }

        if (preg_match('/^# (.+)$/', $first, $m)) {
            return '\section{'.$this->inline($m[1]).'}';
        }

        if (trim($block) === '---') {
            return '\msseparatore';
        }

        if (str_starts_with(ltrim($first), '```')) {
            return $this->convertCodeFence($lines);
        }

        if (str_starts_with(ltrim($first), '> ')) {
            return $this->convertBlockquote($lines);
        }

        if (preg_match('/^- \[ \] /', $first)) {
            return $this->convertCheckboxList($lines);
        }

        if (preg_match('/^- /', $first)) {
            return $this->convertBulletList($lines);
        }

        if (preg_match('/^\d+\. /', $first)) {
            return $this->convertNumberedList($lines);
        }

        if (str_starts_with(trim($first), '|')) {
            return $this->convertTable($lines);
        }

        if (preg_match('/^\*\*[^*\n]+\*\*$/', $first) && count($lines) > 1) {
            $label = $this->inline($first);
            $rest = $this->inline(implode(' ', array_slice($lines, 1)));

            return $label.'\par'."\n".$rest;
        }

        return $this->inline(trim($block));
    }

    private function convertCodeFence(array $lines): string
    {
        array_shift($lines); // riga di apertura ```lang
        if (trim(end($lines)) === '```') {
            array_pop($lines);
        }

        return "\\begin{lstlisting}\n".implode("\n", $lines)."\n\\end{lstlisting}";
    }

    private function convertBlockquote(array $lines): string
    {
        $text = implode(' ', array_map(
            static fn (string $l): string => preg_replace('/^> ?/', '', $l),
            $lines,
        ));

        return "\\begin{quote}\n".$this->inline($text)."\n\\end{quote}";
    }

    private function convertCheckboxList(array $lines): string
    {
        $items = array_map(
            fn (string $l): string => '\item $\square$ '.$this->inline(preg_replace('/^- \[ \] /', '', $l)),
            $lines,
        );

        return "\\begin{itemize}\n".implode("\n", $items)."\n\\end{itemize}";
    }

    private function convertBulletList(array $lines): string
    {
        $items = [];
        foreach ($lines as $line) {
            $indented = (bool) preg_match('/^\s{2,}- /', $line);
            $text = preg_replace('/^\s*- /', '', $line);
            $items[] = ($indented ? '  ' : '').'\item '.$this->inline($text);
        }

        return "\\begin{itemize}\n".implode("\n", $items)."\n\\end{itemize}";
    }

    private function convertNumberedList(array $lines): string
    {
        $items = array_map(
            fn (string $l): string => '\item '.$this->inline(preg_replace('/^\d+\. /', '', $l)),
            $lines,
        );

        return "\\begin{enumerate}\n".implode("\n", $items)."\n\\end{enumerate}";
    }

    private function convertTable(array $lines): string
    {
        $rows = array_values(array_filter($lines, static fn (string $l): bool => trim($l) !== ''));
        $header = $this->splitRow($rows[0]);
        $separator = $this->splitRow($rows[1]);
        $bodyRows = array_slice($rows, 2);

        $colSpec = '@{}';
        foreach ($separator as $sep) {
            $colSpec .= str_ends_with(trim($sep), ':')
                ? '>{\raggedleft\arraybackslash}X'
                : 'X';
        }
        $colSpec .= '@{}';

        $headerCells = implode(' & ', array_map(
            fn (string $c): string => '\thc{'.$this->inline($c).'}',
            $header,
        ));

        $bodyLatex = implode("\n", array_map(
            fn (string $row): string => implode(' & ', array_map(
                fn (string $cell): string => $this->inline($cell),
                $this->splitRow($row),
            )).' \\\\',
            $bodyRows,
        ));

        return "\\mdtabella{{$colSpec}}{{$headerCells}}{%\n{$bodyLatex}\n}";
    }

    /**
     * @return list<string>
     */
    private function splitRow(string $row): array
    {
        $trimmed = trim($row);
        $trimmed = preg_replace('/^\|/', '', $trimmed);
        $trimmed = preg_replace('/\|$/', '', $trimmed);

        return array_map(trim(...), explode('|', $trimmed));
    }

    /**
     * Applica il markdown inline (code span, link, grassetto) a un frammento
     * di testo semplice. Ognuno dei tre viene estratto in un placeholder GIA'
     * come LaTeX finale (contenuto interno già passato da LatexEscaper), PRIMA
     * di escapare il testo circostante: se l'escape del testo semplice
     * avvenisse dopo aver inserito `\textbf{...}`/`\href{...}` letterali,
     * ri-escaperebbe i loro backslash. Estrarre-poi-escapare-il-resto-poi-
     * reinserire evita il problema per costruzione, per tutti e tre i casi
     * allo stesso modo (stesso principio già usato da LatexEscaper::MAP con
     * strtr, qui applicato a livello di markup invece che di singolo carattere).
     */
    private function inline(string $text): string
    {
        $placeholders = [];
        $store = function (string $latex) use (&$placeholders): string {
            $key = "\0PH".count($placeholders)."\0";
            $placeholders[$key] = $latex;

            return $key;
        };

        $text = (string) preg_replace_callback(
            '/`([^`]+)`/',
            fn (array $m): string => $store('\texttt{'.LatexEscaper::escape($m[1]).'}'),
            $text,
        );

        $text = (string) preg_replace_callback(
            '/\[([^\]]+)\]\(([^)]+)\)/',
            function (array $m) use ($store): string {
                $label = trim($m[1], '`');
                $url = $m[2];

                if (str_ends_with($url, '.md') || str_contains($url, '.md#')) {
                    return $store('\texttt{'.LatexEscaper::escape($label).'}');
                }

                return $store('\href{'.$url.'}{'.LatexEscaper::escape($label).'}');
            },
            $text,
        );

        $text = (string) preg_replace_callback(
            '/\*\*([^*]+)\*\*/',
            fn (array $m): string => $store('\textbf{'.LatexEscaper::escape($m[1]).'}'),
            $text,
        );

        $text = LatexEscaper::escape($text);

        return strtr($text, $placeholders);
    }
}
```

`strtr($text, $placeholders)` per il reinserimento finale, non un ciclo di `str_replace`: stesso motivo per cui `LatexEscaper::escape()` usa `strtr` — una singola sostituzione simultanea, senza rischio che il contenuto LaTeX di un placeholder (che può contenere `\0`-marker letterali solo se annidati, caso che qui non si presenta perché i tre `preg_replace_callback` sopra operano in sequenza su placeholder già opachi) interferisca con un altro.

Aggiungere questo test esplicito nel Task 5 Step 1 (oltre a quelli già elencati), a riprova che l'ordine store-poi-escape-poi-reinserisci è corretto anche quando testo da escapare e markup convivono nella stessa riga:

```php
it('does not corrupt bold or link markup when the surrounding text needs escaping', function () {
    $out = (new MarkdownToLatexConverter)->convert(
        '100% **importante** & vero, vedi [qui](https://x.test/a&b)'
    );

    expect($out)->toBe(
        '100\% \textbf{importante} \& vero, vedi \href{https://x.test/a&b}{qui}'
    );
});
```

- [ ] **Step 4: Eseguire i test e correggere finché passano tutti**

Run: `cd orchestrator && vendor/bin/pest tests/Unit/Support/Latex/MarkdownToLatexConverterTest.php`
Expected: PASS su tutti i case, incluso quello aggiunto allo Step 3.

- [ ] **Step 5: Lint + analisi statica**

Run: `cd orchestrator && vendor/bin/pint app/Support/Latex/MarkdownToLatexConverter.php tests/Unit/Support/Latex/MarkdownToLatexConverterTest.php && vendor/bin/phpstan analyse --memory-limit=1G`
Expected: nessun errore.

- [ ] **Step 6: Commit**

```bash
cd orchestrator
git add app/Support/Latex/MarkdownToLatexConverter.php tests/Unit/Support/Latex/MarkdownToLatexConverterTest.php
git commit -m "feat: aggiunge MarkdownToLatexConverter per il manuale di collaudo dettagliato"
```

---

### Task 6: Vista LaTeX + wiring per il PDF dettagliato (`collaudo-dettagliato.blade.php` → LaTeX)

**Files:**
- Create: `orchestrator/resources/views/latex/collaudo-dettagliato.tex.blade.php`
- Modify: `orchestrator/app/Console/Commands/CollaudoGenerateCommand.php` (metodo `buildDetailedPdf`, righe 80-106)
- Test: `orchestrator/tests/Feature/Console/CollaudoGenerateDetailedTest.php` (esistente)
- Delete: `orchestrator/resources/views/pdf/collaudo-dettagliato.blade.php`

**Interfaces:**
- Consumes: `App\Support\Latex\MarkdownToLatexConverter::convert()` (Task 5), `App\Support\Latex\LatexPdfCompiler::compile()` (Task 3), `App\Support\Latex\LatexEscaper::escape()` (Task 2).

- [ ] **Step 1: Verificare che il test esistente sia ancora significativo**

Il test esistente (`tests/Feature/Console/CollaudoGenerateDetailedTest.php`) verifica solo `%PDF` + dimensione > 200KB. Questa soglia è tarata su dompdf/HTML: la resa LaTeX di 8 file Markdown (uno da 302KB) potrebbe produrre un PDF di dimensione molto diversa (in genere PDF LaTeX sono **più compatti** di PDF HTML equivalenti per via del font embedding differente). Aggiornare la soglia dopo aver visto la dimensione reale allo Step 5 (non indovinarla ora).

- [ ] **Step 2: Scrivere la vista LaTeX**

`resources/views/latex/collaudo-dettagliato.tex.blade.php`:

```blade
\documentclass[italiano]{montagnaservizi}
\titolodoc{Documento di collaudo}
\sottotitolo{{{ $titolo }}}
\begin{document}

\copertina

\indicedoc
\clearpage

@foreach ($sections as $section)
\section{{{ '{' }}{{ $section['titolo'] }}{{ '}' }}}

{!! $section['latex'] !!}

\clearpage
@endforeach

\end{document}
```

Nota sulla stessa cautela graffe del Task 4: verificare con `view(...)->render()` in tinker che `\sottotitolo{{{ $titolo }}}` produca `\sottotitolo{Testo}` letterale e non venga confuso da Blade — se ambiguo, passare `$titolo` già come stringa `"\\sottotitolo{".$escaped."}"` pre-composta dal Command ed emetterla con `{!! $sottotitoloLatex !!}` invece di ricostruire le graffe in Blade, stesso principio del Task 4 Step 4.

`{!! !!}` (non `{{ }}`) per `$section['latex']`: il contenuto è già LaTeX prodotto da `MarkdownToLatexConverter` (già passato da `LatexEscaper` al suo interno), **non** va ri-escappato da Blade (che comunque farebbe HTML-escape, irrilevante ma fuorviante qui — usare sempre `{!! !!}` per contenuto già-LaTeX pre-renderizzato, mai `{{ }}`).

- [ ] **Step 3: Riscrivere `buildDetailedPdf`**

Sostituire in `app/Console/Commands/CollaudoGenerateCommand.php`:

```php
    public function buildDetailedPdf(string $fase): string
    {
        $sections = array_map(
            static fn (array $entry): array => [
                'titolo' => LatexEscaper::escape($entry['titolo']),
                'latex' => (new MarkdownToLatexConverter)->convert(
                    self::stripOwnTitle(file_get_contents(base_path("docs/collaudo/{$entry['file']}"))),
                ),
            ],
            self::DETAILED_FILES,
        );

        $tex = view('latex.collaudo-dettagliato', [
            'titolo' => LatexEscaper::escape(
                'Fase 0 (Fondazioni) + Fase 1 (Ticketing core) + Fase 1A (Landing, Login, Recupero password)',
            ),
            'sections' => $sections,
        ])->render();

        $pdfPath = app(LatexPdfCompiler::class)->compile($tex);

        $filename = sprintf('collaudo-dettagliato-fase-%s-%s.pdf', $fase, now()->format('Ymd-His'));
        $disk = Storage::build(['driver' => 'local', 'root' => storage_path('app')]);
        $disk->put("collaudo/{$filename}", file_get_contents($pdfPath));

        return storage_path("app/collaudo/{$filename}");
    }

    /**
     * Ogni file di docs/collaudo/ apre con un h1 (`# Titolo...`) che duplica
     * il titolo già mostrato nell'indice del documento combinato (colonna
     * "titolo" di DETAILED_FILES): senza questa rimozione, il PDF LaTeX
     * mostrerebbe lo stesso titolo due volte consecutive (una volta come
     * \section{} dell'indice generale, una volta come \section{} convertito
     * dal primo h1 del file). dompdf/HTML non aveva questo problema perché
     * l'h1 diventava semplicemente un sottotitolo visivo dentro la sezione,
     * mai un ingresso nell'indice — qui invece ogni \section{} genera una
     * voce di indice reale (\indicedoc), quindi il duplicato sarebbe visibile
     * due volte anche li'.
     */
    private static function stripOwnTitle(string $markdown): string
    {
        return (string) preg_replace('/^# .+\n+/', '', $markdown, limit: 1);
    }
```

Aggiungere gli `use`:

```php
use App\Support\Latex\LatexEscaper;
use App\Support\Latex\LatexPdfCompiler;
use App\Support\Latex\MarkdownToLatexConverter;
```

Rimuovere ora (non più usati da nessun metodo) `use Barryvdh\DomPDF\Facade\Pdf;` e `use Illuminate\Support\Str;` (quest'ultimo usato solo da `Str::markdown` nel vecchio `buildDetailedPdf` e dal vecchio helper `separateBoldLabelsIntoOwnParagraph`, entrambi da rimuovere: il nuovo `MarkdownToLatexConverter` gestisce grassetto/paragrafi autonomamente, quel workaround era specifico di CommonMark/dompdf).

Rimuovere il metodo privato `separateBoldLabelsIntoOwnParagraph` (righe 108-120 del file originale): non serve più. Il Task 5 (`MarkdownToLatexConverter::convertBlock`) già riproduce lo stesso comportamento nativamente (etichetta in grassetto su riga propria seguita da testo sulla riga successiva → `\par` esplicito, non un semplice spazio), quindi questo task non deve reimplementare nulla — solo rimuovere il vecchio helper CommonMark-specifico, ormai morto.

- [ ] **Step 4: Eliminare la vista dompdf superata**

```bash
cd orchestrator
git rm resources/views/pdf/collaudo-dettagliato.blade.php
```

- [ ] **Step 5: Eseguire i test, osservare la dimensione reale, aggiornare la soglia**

Run: `cd orchestrator && vendor/bin/pest tests/Feature/Console/CollaudoGenerateDetailedTest.php`

Se fallisce solo sulla soglia `toBeGreaterThan(200_000)`, ispezionare `strlen($contents)` reale (aggiungere temporaneamente un `dump()` o controllare `ls -la storage/app/collaudo/` dopo un run manuale di `php artisan collaudo:generate 0-1`) e sostituire la soglia con un valore coerente con l'evidenza osservata (non un numero indovinato) — commentare nel test **perché** quel numero (dimensione osservata in una build di riferimento, con nota che può oscillare leggermente tra run per via di metadati/timestamp nel PDF).

- [ ] **Step 6: Lint + analisi statica**

Run: `cd orchestrator && vendor/bin/pint app/Console/Commands/CollaudoGenerateCommand.php && vendor/bin/phpstan analyse --memory-limit=1G`
Expected: nessun errore.

- [ ] **Step 7: Commit**

```bash
cd orchestrator
git add -A
git commit -m "feat: genera il PDF di collaudo dettagliato con LaTeX invece di dompdf/CommonMark"
```

---

### Task 7: TeX Live nell'immagine Docker di sviluppo

**Files:**
- Modify: `orchestrator/docker/php/Dockerfile`

**Interfaces:**
- Nessuna interfaccia di codice: task infrastrutturale, richiesto perché `pdflatex` non esiste nell'immagine `app` attuale.

- [ ] **Step 1: Aggiungere i pacchetti TeX Live verificati**

In `docker/php/Dockerfile`, aggiungere alla riga `apk add --no-cache` esistente (quella con `postgresql-dev libzip-dev ...`) i pacchetti TeX Live **verificati in questa sessione** con una build reale:

```dockerfile
RUN apk add --no-cache \
        postgresql-dev \
        libzip-dev \
        icu-dev \
        oniguruma-dev \
        fcgi \
        shadow \
        texlive \
        texmf-dist-latex \
        texmf-dist-latexrecommended \
        texmf-dist-latexextra \
        texmf-dist-fontsrecommended \
        texmf-dist-fontsextra \
        texmf-dist-langitalian \
        texmf-dist-plaingeneric \
        texmf-dist-pictures \
        poppler-utils \
    && apk add --no-cache --virtual .build-deps ${PHPIZE_DEPS} \
    ...
```

(`poppler-utils` fornisce `pdftotext`, usato dai test del Task 4/5 per verificare il testo renderizzato — non serve a runtime dall'applicazione, solo ai test.)

- [ ] **Step 2: Ricostruire l'immagine e verificare**

```bash
cd orchestrator
docker compose build app
docker compose run --rm app pdflatex --version
docker compose run --rm app pdftotext -v
```

Expected: entrambi i comandi rispondono con la versione installata, nessun errore "not found".

- [ ] **Step 3: Rigenerare il PDF di collaudo dentro il container, come prova end-to-end**

```bash
docker compose up -d db redis app
docker compose exec app php artisan collaudo:generate 0-1
```

Expected: `PDF dettagliato generato: ...` (o, se i file del manuale dettagliato non esistono in quell'ambiente, `PDF generato: ...`), nessun errore.

- [ ] **Step 4: Aggiornare CLAUDE.md**

Aggiungere una sezione (seguendo lo stile delle sezioni esistenti per story, vedi il resto del file) che documenti: la scelta di installare TeX Live solo nell'immagine di sviluppo, i pacchetti Alpine esatti verificati, e un riassunto dei 3 bug della classe `montagnaservizi.cls` scoperti e corretti (mstabella/storicorevisioni/presenze/azioni/voci — tabularx letto verbatim, non tollera newenvironment; firme — dimexpr+minipage; mdtabella nuovo — xltabular eredita lo stesso vincolo di tabularx).

- [ ] **Step 5: Commit**

```bash
cd orchestrator
git add docker/php/Dockerfile CLAUDE.md
git commit -m "feat: installa TeX Live nell'immagine Docker di sviluppo per la generazione PDF via LaTeX"
```

---

### Task 8: TeX Live in CI

**Files:**
- Modify: `orchestrator/.github/workflows/ci.yml`

**Interfaces:**
- Nessuna interfaccia di codice: richiesto perché i test dei Task 3/4/5/6 girano nel job `quality` (`ubuntu-latest`, PHP nativo, non containerizzato) e falliscono senza `pdflatex`/`pdftotext`.

- [ ] **Step 1: Aggiungere uno step di installazione TeX Live prima di Pest**

In `.github/workflows/ci.yml`, job `quality`, inserire uno step tra "Build front-end assets" e "Pint" (o comunque prima di "Pest (con coverage)"):

```yaml
      - name: Install TeX Live
        run: |
          sudo apt-get update
          sudo apt-get install -y --no-install-recommends \
            texlive-latex-extra texlive-fonts-extra texlive-lang-italian poppler-utils
```

Nota: i nomi pacchetto Debian/Ubuntu (`texlive-latex-extra`, `texlive-fonts-extra`, `texlive-lang-italian`) sono diversi da quelli Alpine (`texmf-dist-*`) usati nel Task 7 — stessa collezione TeX Live logica, packaging diverso per distro. Verificare con la build CI reale (Step 2) che l'insieme scelto basti; se mancano pacchetti (`montserrat.sty`/`tcolorbox.sty`/`tocloft.sty` non trovati), il log CI di Pest lo segnalerà esplicitamente (LaTeX Error: File `X.sty' not found) — aggiungere il pacchetto Debian corrispondente (cercabile con `apt-cache search <nome>` nello step stesso in caso di debug) e ripetere finché il job passa. Non committare "a scatola chiusa": la riga finale di questo step deve essere quella con cui il job `quality` è passato per davvero almeno una volta.

- [ ] **Step 2: Aprire una PR di prova (o pushare su un branch con PR aperta) e osservare l'esito reale del job `quality`**

Questo step non è automatizzabile da riga di comando isolata: richiede il ciclo reale CI di GitHub Actions. Iterare sulla lista pacchetti dello Step 1 finché "Pest (con coverage)" passa, includendo i test dei Task 3/4/5/6.

- [ ] **Step 3: Commit**

```bash
cd orchestrator
git add .github/workflows/ci.yml
git commit -m "ci: installa TeX Live per compilare i PDF di collaudo LaTeX durante i test"
```

---

### Task 9: Rimuovere dompdf e ripulire

**Files:**
- Modify: `orchestrator/composer.json`
- Modify: `orchestrator/composer.lock` (rigenerato da composer, non a mano)

**Interfaces:**
- Nessuna: pulizia finale, nessun altro punto del codice usa più `barryvdh/laravel-dompdf` dopo i Task 4/6 (verificato con grep prima di iniziare questo piano: l'unico consumer era `CollaudoGenerateCommand`).

- [ ] **Step 1: Verificare che non resti alcun uso di dompdf**

```bash
cd orchestrator
grep -rn "Barryvdh\|dompdf\|DomPDF" app/ resources/views/ tests/ --include="*.php" --include="*.blade.php"
```

Expected: nessun risultato. Se emerge un uso non previsto da questo piano, fermarsi e valutare separatamente prima di procedere alla rimozione del pacchetto.

- [ ] **Step 2: Rimuovere il pacchetto**

```bash
cd orchestrator
composer remove barryvdh/laravel-dompdf
```

- [ ] **Step 3: Rieseguire l'intera suite per conferma**

Run: `cd orchestrator && vendor/bin/pint --test && vendor/bin/phpstan analyse --memory-limit=1G && php -d memory_limit=1G vendor/bin/pest`
Expected: PASS su tutto (nota memory_limit, vedi CLAUDE.md — gotcha già documentato su `php artisan test` che ignora `-d memory_limit`).

- [ ] **Step 4: Commit**

```bash
cd orchestrator
git add composer.json composer.lock
git commit -m "chore: rimuove barryvdh/laravel-dompdf, sostituito dal motore LaTeX"
```

---

### Task 10: Versione 0.3.2 e verifica end-to-end finale

**Files:**
- Modify: `orchestrator/CHANGELOG.md`

**Interfaces:**
- Nessuna.

- [ ] **Step 1: Aggiungere la voce di CHANGELOG**

In cima a `CHANGELOG.md`, sotto l'header e sopra `## [0.3.1]`:

```markdown
## [0.3.2] - {DATA_ODIERNA}

**Motore PDF di collaudo su LaTeX**, in sostituzione di dompdf/HTML, con la carta
intestata ufficiale Montagna Servizi (classe `montagnaservizi.cls`).

### Aggiunto

- `App\Support\Latex\{LatexEscaper,LatexPdfCompiler,MarkdownToLatexConverter}`: motore di
  generazione PDF via pdfLaTeX (TeX Live), con compilazione doppia e gestione errori con
  log di compilazione.
- Classe LaTeX brandizzata `resources/latex/montagnaservizi.cls` (copertina, tabelle,
  box nota/attenzione/requisito, elenchi numerati a fasi, firme, tabelle multi-pagina) —
  importata da un progetto Claude Design dedicato e corretta (6 costrutti non
  compilavano nella versione originale, vedi CLAUDE.md per i dettagli).
- TeX Live nell'immagine Docker di sviluppo (`docker/php/Dockerfile`) e in CI — non in
  produzione/UAT, dove `collaudo:generate` non viene mai eseguito.

### Rimosso

- Dipendenza `barryvdh/laravel-dompdf`, non più usata da nessuna parte dell'applicazione.

### Modificato

- Sia il PDF di collaudo sintetico sia quello dettagliato sono ora generati da sorgente
  LaTeX invece che da viste Blade/HTML renderizzate con dompdf.
```

(sostituire `{DATA_ODIERNA}` con la data reale del giorno in cui questo task viene eseguito, formato `YYYY-MM-DD`, coerente con le voci precedenti del changelog)

- [ ] **Step 2: Verifica end-to-end manuale finale**

```bash
cd orchestrator
docker compose exec app php artisan collaudo:generate 0-1
```

Aprire visivamente il PDF prodotto (`storage/app/collaudo/collaudo-dettagliato-fase-0-1-*.pdf` più recente) e verificare a occhio: carta intestata Montagna Servizi presente su ogni pagina, indice cliccabile, tabelle leggibili (incluse quelle molto lunghe di Fase 0/Fase 1 che si spezzano su più pagine con intestazione ripetuta), nessun carattere `\%`/`\_`/`\&` visibile letteralmente nel testo (segno di escaping fallito), nessuna sezione vuota o troncata.

- [ ] **Step 3: Applicare la pulizia della cartella storage/app/collaudo/ già stabilita in questa stessa conversazione**

Come da richiesta precedente dell'utente in questa stessa sessione di lavoro: dopo aver generato la nuova versione, mantenere solo l'ultimo PDF sintetico e l'ultimo PDF dettagliato in `storage/app/collaudo/`, eliminando le versioni precedenti (incluse quelle prodotte da dompdf prima di questo piano).

- [ ] **Step 4: Commit**

```bash
cd orchestrator
git add CHANGELOG.md
git commit -m "chore: bump versione a 0.3.2"
```

---

## Note per chi esegue questo piano

- **Non eseguire i Task 3-6 (quelli con test che chiamano `pdflatex`) su un ambiente senza TeX Live** senza prima aver completato almeno la verifica manuale del Task 1 Step 2 (build Docker one-off) — altrimenti ogni test fallirà con "command not found" e sembrerà un bug nel codice invece che un gap d'ambiente.
- Il Task 8 (CI) richiede un ciclo reale di GitHub Actions per essere verificato: non è completabile "alla scrivania". Se l'esecutore di questo piano non ha accesso per aprire PR/osservare Action reali, segnalarlo esplicitamente invece di assumere che l'elenco pacchetti Debian proposto allo Step 1 sia corretto al primo colpo.
- I bug della classe `montagnaservizi.cls` descritti in apertura di questo piano sono stati diagnosticati e corretti con build Docker reali in questa sessione (non sono ipotesi): il file in
  `/private/tmp/claude-501/-Users-alessiopiccioli-Documents-LAVORO-MS-SOFTWARE-mstickets/3f7e8a73-e51c-4760-aabb-e6a6406e2658/scratchpad/latex-design/montagnaservizi.cls`
  è già corretto e testato (compilazione doppia riuscita, tutti i 13 costrutti verificati, tabella da 89 righe su 3 pagine). Se quello scratchpad non esiste più al momento dell'esecuzione, il Task 1 Step 1 spiega come ricostruirlo.
