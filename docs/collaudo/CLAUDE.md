# Collaudo — manifest, manuale narrativo, PDF pdfLaTeX

Si carica quando lavori sotto `docs/collaudo/*` (o su `app/Console/Commands/Collaudo*.php`, `app/Support/
Latex/*`, `app/Support/Collaudo/*` — apri anche questo file lì per gli stessi motivi). Vedi anche la radice
del repo per il checklist obbligatorio di 5 passi ad ogni fine fase (qui il dettaglio operativo di ciascun
passo) e `docker/CLAUDE.md` per i pacchetti Alpine di TeX Live.

## Processo — dettaglio operativo dei 5 passi

1. `docs/collaudo/fase-<N>.php` — manifest topic → test numerati (es. `F2-01`) → riferimento a un test
   automatico REALMENTE esistente (`php artisan collaudo:verify-manifest <N>` deve passare).
2. **Il manuale narrativo di collaudo** (`docs/collaudo/0N-fase-N.md`, stesso formato per-test di
   `03-fase-1.md`) è sempre l'ULTIMO passo dello sviluppo di una fase, dopo che tutte le story sono complete
   e il manifest passa — mai in parallelo alle story, perché richiede di leggere il codice/test automatico
   REALE per descrivere passi/esito accurati. Va aggiornato in coerenza anche `README.md`,
   `00-istruzioni-generali.md`, `01-matrice-tracciabilita.md`, `07-registro-esiti.md` (rinumerato in avanti
   ad ogni nuova fase narrativa, vedi sotto).
3. `php artisan collaudo:generate <N>` — PDF con carta intestata, Parte 1 (istruzioni) + una sezione per
   topic. **Aggiungere sempre la fase a `CollaudoGenerateCommand::FASE_NARRATIVE_FILES`/`FASE_TITLES`**
   quando si scrive il manuale narrativo: senza, il comando ripiega SILENZIOSAMENTE (nessun errore) sul solo
   PDF sintetico da manifest invece del PDF dettagliato. `storage/app/collaudo/` deve contenere sempre e
   solo l'ultima versione generata per ciascuna fase: `CollaudoGenerateCommand` cancella da sé ogni PDF
   precedente della stessa fase/variante — non disabilitare né aggirare questo comportamento.
4. Il deploy su UAT (automatico al merge su `develop`) deve riflettere lo stato descritto nel manifest —
   vedi `deploy/CLAUDE.md`.
5. Se un test del collaudo fallisce durante una sessione di collaudo reale, il test automatico corrispondente
   va rivisto: non copriva il caso reale che ha fatto fallire il collaudo.

## Rinumerazione dei file cumulativi ad ogni nuova fase narrativa

Aggiungere un nuovo `0N-fase-N.md` richiede SEMPRE di rinumerare `07-registro-esiti.md`/
`08-verbale-collaudo.md` in avanti (diventano `08-`/`09-` quando si aggiunge Fase 4, `09-`/`10-` alla Fase 5,
ecc.): il nuovo file narrativo prende sempre il numero immediatamente successivo all'ultimo file di fase
esistente, mai "per intuito". Verificare SEMPRE con `git log --diff-filter=R --summary --oneline --
docs/collaudo/` come le fasi precedenti hanno fatto la rinumerazione.

## Generazione PDF di collaudo via pdfLaTeX (v0.3.2, sostituisce dompdf)

- `collaudo:generate` non usa più `dompdf` (mai stato in grado di riprodurre fedelmente la carta intestata):
  la Parte 1 e il PDF dettagliato sono compilati con `pdflatex` reale, via
  `App\Support\Latex\LatexPdfCompiler` su un template Blade (`*.tex.blade.php`, richiede `View::addExtension`
  registrato) che estende la classe vendorizzata `resources/latex/montagnaservizi.cls`.
  `App\Support\Latex\LatexEscaper` sanitizza ogni valore interpolato (mai un `{{ }}`/`{{{ }}}` Blade nudo su
  un campo LaTeX: serve `{!! !!}` + una graffa letterale extra per gli argomenti di macro),
  `App\Support\Latex\MarkdownToLatexConverter` converte i manuali markdown nel corpo del PDF dettagliato.
- Pacchetti Alpine e binari: vedi `docker/CLAUDE.md`.
- **Bug reali trovati e corretti durante l'introduzione di questa pipeline** (tutti scoperti compilando
  `pdflatex` contro contenuto REALE, mai da test sintetici scritti a priori):
  - `montagnaservizi.cls`: `tabularx` legge il proprio contenuto **verbatim** e non tollera un
    `\newenvironment` che lo avvolga — ha rotto sia `mstabella` sia (più tardi) `mdtabella` (`xltabular`,
    variante multi-pagina, eredita lo stesso vincolo). Un terzo bug in `\firme` era un `\dimexpr` calcolato
    dentro una `minipage` che non si espandeva correttamente. Aggiunto anche `\usepackage{amssymb}`
    (mancante: senza, il simbolo bullet di `itemize` non è definito e `pdflatex` fallisce in modo fatale).
  - `MarkdownToLatexConverter`: 4 bug (marker di placeholder annidato che trapelava per link stile
    `[x.md](x.md)`; un blocco "etichetta in grassetto" che appiattiva una lista puntata subito successiva
    senza riga vuota; elenchi con wrap manuale a 70-100 caratteri spezzati in `\item` spuri; un paragrafo
    interrotto da una lista fuso in un unico blocco). Più una pipe `|` non escapata in una cella di tabella
    markdown che mandava `pdflatex` in errore fatale (fix in `normalizeRowCells`).
  - `LatexEscaper`: mappa Unicode incompleta — mancavano `✓`/`−`/`≥`/`≤` usate nei manuali reali.
  - **Se una futura modifica a questa pipeline sembra "ovviamente corretta" ma tocca `montagnaservizi.cls`/
    `MarkdownToLatexConverter`/`LatexEscaper`, ripetere una compilazione reale end-to-end
    (`collaudo:generate`) prima di considerarla verificata, non fidarsi della sola suite di unit test.**
- **Ambiente locale di sviluppo (non Docker/CI)**: manca il pacchetto texlive `csquotes.sty`
  (`CollaudoGenerateCommandTest`/`CollaudoGenerateDetailedTest`/`LatexPdfCompilerTest` falliscono con "File
  `csquotes.sty` not found" anche su un checkout pulito) — gap dell'host, non del codice: verificare sempre
  con `git stash` se un fallimento di questi 3 file è già presente prima di attribuirlo al proprio lavoro.
  Eseguire dentro il container Docker `app` quando serve una verifica reale (bind mount, nessun rebuild
  necessario).

## Gotcha apostrofo — DUE varianti distinte, non confonderle

- **Variante 1 (`collaudo:verify-manifest`, confronto sui BYTE GREZZI del file di test)**:
  `CollaudoTestReference::description()` fa `str_contains(file_get_contents($testFile), $description)` sui
  byte grezzi del file PHP sorgente del test, non sulla stringa PHP già valutata. Un nome di test Pest con un
  apostrofo dentro una stringa a singoli apici (`test('...actor\'s own...', ...)`) contiene letteralmente
  `\'` nei byte grezzi, ma lo stesso apostrofo scritto nel manifest (altra stringa PHP a singoli apici) viene
  valutato da PHP a un apice singolo senza backslash — le due stringhe NON combaciano mai, "riferimento
  mancante" anche se il test esiste. Soluzioni, in ordine di preferenza: (a) scegliere per il manifest un
  test REALE equivalente ma senza apostrofo nel nome; (b) se nessuna alternativa apostrofo-libera copre lo
  stesso AC (es. `CreateProjectAndTicketActionsTest.php`, US-505), il riferimento `test_automatico` può
  essere il solo percorso del file, SENZA `::descrizione` — `verify-manifest` allora si limita a
  `file_exists()` (comportamento supportato, non un workaround, solo più debole: non rileva la cancellazione
  di un singolo test). Verificare con `grep -rnE "test\('[^']*\\\\'" tests/` quali file hanno questo pattern
  prima di scegliere quali test citare in un nuovo manifest.
- **Variante 2 (VISUALIZZAZIONE per un tester umano — manuale dettagliato, matrice, registro esiti)**: il
  manifest porta nel suffisso `::descrizione` un backslash letterale prima dell'apostrofo quando serve per
  far combaciare `verify-manifest` (variante 1). Quel backslash letterale NON va mai mostrato in un documento
  per un tester umano: ripulire con `str_replace("\\'", "'", $testDescription)` prima di qualunque
  visualizzazione, ma MAI prima del confronto passato a `verify-manifest` (quello richiede il backslash
  intatto).

## Checkpoint di fine fase — generazione con script PHP usa-e-getta (dalle Fasi 4-8)

- Con decine di test su molti topic, generare `NN-fase-N.md` a mano diventa fragile: più affidabile uno
  script PHP one-off (fuori dal repo, non committato — es. `/tmp/gen-faseN-collaudo.php` + un file dati con
  le sole informazioni non derivabili dal manifest: priorità, ruolo, prerequisiti, procedura passo-passo per
  i casi MANUALE UI) che legge `docs/collaudo/fase-N.php` (già scritto a mano) e produce ogni blocco
  `### FN-xx — ...` col template esatto già in uso (13 campi: Obiettivo/Riferimenti/Modalità/Priorità/Ruolo/
  Prerequisiti/Dati di test/Stato iniziale/Procedura/Risultato finale/Controlli negativi/Evidenze/Criterio di
  superamento/Ripristino/Campi di consuntivazione). Per i casi AUTOMATICO quasi tutto è derivabile per
  default dalla `descrizione`/`test_automatico` del manifest — riduce l'autoria manuale ai soli casi MANUALE
  UI. Stesso script serve anche per `01-matrice-tracciabilita.md`/`11-registro-esiti.md` (righe derivabili
  dallo stesso manifest + metadati raccolti, append in coda invece di trascrizione manuale riga per riga).
- **Aggiornare anche i 5 file "vivi" cumulativi ad ogni fase**, non solo il manifest/manuale della fase
  nuova: `00-istruzioni-generali.md` (§1 changelog versione, §2 conteggio totale, §3 tabella argomenti + §4
  ambito escluso + §17 conteggio test), `01-matrice-tracciabilita.md` (contatori + append righe),
  `11-registro-esiti.md` (append + contatore aggregato), `12-verbale-collaudo.md` (conteggio test),
  `README.md` (riepilogo + indice). Il PDF generato include SEMPRE questi file cumulativi in testa/coda
  (`COMMON_PREFIX_FILES`/`COMMON_SUFFIX_FILES` di `CollaudoGenerateCommand`): se non aggiornati, il PDF della
  nuova fase mostra ancora i conteggi della fase precedente nell'indice, pur avendo il proprio manuale
  dettagliato corretto in mezzo.

## Note puntuali da checkpoint precedenti

- **Nessun comando `tickets:backfill-dates` esiste in questo repository**: il backfill di
  `released_at`/`done_at` mancanti è già parte dello stage `derive` di `v1:import`
  (`App\Import\Stages\DeriveStage::backfillTimestamps()`), eseguito automaticamente ad ogni
  `v1:import --anonymize` — se un futuro checkpoint menziona di nuovo "post v1:import +
  tickets:backfill-dates" (probabile copia-incolla), verificare prima con `grep -rn backfill
  app/Console/Commands` che il comando esista davvero.
- Verifica end-to-end su dati reali importati: `bin/load-v1-dump v1dumps/latest.sql` poi
  `docker compose exec app php artisan v1:import --anonymize` (idempotente, rilanciabile) — vedi
  `app/Import/CLAUDE.md`.
- Ogni checkpoint di fine fase deve produrre anche un test automatico END-TO-END nuovo (non solo riusare i
  test già scritti dalle story), che replichi in CI il flusso manuale verificato su dati reali (es.
  `tests/Feature/EndToEnd/Fase4CheckpointEndToEndTest.php`) — un ultimo topic "Checkpoint di fine fase" nel
  manifest della fase, con ID dedicati mai riusati da un topic precedente per lo stesso file.
