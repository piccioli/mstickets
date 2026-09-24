# Dominio Reporting — rendicontazione attività (`ActivityReport`)

Si carica quando lavori sotto `app/Domain/Reporting/*`. Vedi anche `app/Domain/CLAUDE.md` per i pattern
condivisi (in particolare `scopeVisibleTo()`/`isOwnedBy()`) e `app/Domain/Mail/CLAUDE.md` §US-615 per la
notifica E10 legata a questo dominio.

## Schema — Rendicontazione (US-014)

- **Vincolo CHECK multi-colonna, portabile su Postgres e sqlite**: Blueprint di Laravel non ha un metodo
  `check()` generico. Su Postgres un `ALTER TABLE ... ADD CONSTRAINT ... CHECK (...)` via `DB::statement`
  dopo `Schema::create` funziona; su **sqlite** (usato dai test) `ALTER TABLE` non supporta `ADD CONSTRAINT`
  in nessuna forma, quindi il vincolo va **emulato con due trigger** `BEFORE INSERT`/`BEFORE UPDATE` che
  fanno `SELECT RAISE(ABORT, '...')` quando la condizione è falsa — producono la stessa `QueryException`
  lato applicativo di un vero CHECK Postgres. Vedi `2026_07_26_110000_create_activity_reports_table.php`
  (`activity_reports_owner_check`) per il pattern completo, riusabile per qualunque futuro vincolo CHECK
  multi-colonna.
- **Un `unique()` composito su colonne nullable NON basta quando i valori NULL fanno parte della chiave
  logica**: sia Postgres sia sqlite trattano NULL come distinto da se stesso ai fini di un vincolo `UNIQUE`
  standard — righe "duplicate" che differiscono solo per una colonna sempre-NULL passano indisturbate.
  Soluzione: un indice unique su **espressione con `coalesce(colonna, valore_sentinella)`** al posto della
  colonna nullable (stesso pattern dell'indice funzionale case-insensitive `lower(email)`), aggiunto con
  `DB::statement('create unique index ... on ... (coalesce(col, 0), ...)')`. Riusare per qualunque futura
  tabella con un vincolo unique che includa colonne FK nullable "a scelta" (pattern
  `owner_kind`/`owner_*_id`).
  **Attenzione**: non confondere questo pattern con un AC che dice letteralmente "univoco dove/quando la
  colonna non è null" (`email_messages`, US-016) — lì il NULL non fa parte della chiave logica e un
  `coalesce()` sarebbe un bug (renderebbe univoche anche le righe con la colonna NULL). Verificare sempre
  cosa vuole davvero l'AC prima di applicare `coalesce()`.

## PDF report attività — locale per record, filename vs percorso su disco (US-409, §6.5.3)

- **`LogoDataUri::resolve()` (`App\Support\Pdf\LogoDataUri`)** è l'unico punto che incorpora un logo come
  data URI (estratto dal duplicato in `GenerateDocumentationPagePdf`, US-406 — vedi
  `app/Domain/Documentation/CLAUDE.md`): qualunque terzo PDF generato in coda deve riusare questa classe.
- **Un PDF generato in coda con contenuto per-record in una lingua diversa richiede `App::setLocale($report
  ->locale)` esplicito prima del render e il ripristino nel `finally`**: a differenza di un Mailable (dove
  `Mail::to(...)->locale(...)` gestisce da solo lo switch), `Pdf::view(...)->save(...)` renderizza con
  l'`App::getLocale()` corrente del processo — un job in coda che processa report di owner con locale diversi
  in sequenza (stesso worker, stesso processo PHP long-running) lascerebbe l'ultimo locale "vincente" senza
  il ripristino esplicito. Le stringhe statiche della vista passano da `__()` (chiavi in `lang/it.json`/
  `lang/en.json`, stesso pattern §7.6/US-320); gli accessor già locale-aware del model (`periodLabel()`, che
  applica `->locale($this->locale)` a Carbon direttamente) non hanno bisogno di questo wrapping.
- **Il nome del file scaricato (`pdfDownloadFilename()`, da `PLATFORM_ACRONYM`+owner+periodo) è
  deliberatamente diverso dal percorso su disco** (`pdf_path`, sempre `activity-reports/{id}.pdf`) — stesso
  principio del pattern slug/id in Documentation.
- **`ActivityReportPolicy::view()` pre-stubbato era un bug da correggere, non solo estendere**:
  `viewAny()`/`view()` collassavano `.view.any` e `.view.own` nella stessa condizione, quindi un customer con
  solo `.view.own` poteva vedere/scaricare il report di **qualunque** owner. Corretto delegando a
  `ActivityReport::isOwnedBy(User $user): bool` (utente owner diretto, o organizzazione owner di cui l'utente
  è membro). Quando si aggiunge un secondo permesso "own", verificare sempre con un test a due owner diversi
  che ".own" non stia implicitamente autorizzando anche "any".
- **Pulizia del PDF su cancellazione del record (`static::deleting()` in `booted()`) è un hook Eloquent
  accettabile qui**, a differenza del divieto esplicito per side-effect di dominio: è una pulizia tecnica di
  risorsa (stesso principio di spatie/medialibrary), non una regola di business con effetti su un altro
  dominio.

## Comando schedulato `reports:generate-monthly` (US-410, §6.5.2/§9.4)

- **Un comando che deve "scoprire" i propri destinatari (qui: owner attivi) non ha bisogno di una nuova
  relazione Eloquent inversa solo per quello**: la query parte da
  `Ticket::whereBetween('done_at', [...])->pluck('requester_id')->unique()` e poi filtra `User`/
  `Organization` con `whereIn`/`whereHas`. Aggiungere una relazione solo per un comando di scoperta owner è
  un'astrazione prematura finché nient'altro nel dominio ne ha bisogno.
- **Un'Action "singolo entry point" che già rifiuta i duplicati con un'eccezione (`CreateActivityReport::
  run()`) non basta da sola per un comando batch idempotente con `--dry-run`**: il comando deve ripetere lo
  stesso controllo `exists()` (owner+periodo) PRIMA di chiamare l'Action — in `--dry-run` non si può invocare
  l'Action solo per scoprire se esiste già, e usare l'eccezione come unico segnale renderebbe il conteggio
  "saltati" indistinguibile da un vero errore. Il `try/catch` sull'Action resta comunque per la vera race
  condition, ma non è la via primaria per l'idempotenza.
- `ActivityReport::scopeVisibleTo(Builder $query, User $user)` è il query-equivalente di `isOwnedBy()` — vedi
  `app/Domain/CLAUDE.md`.
- **Una Resource Filament in sola lettura può restare tale semplicemente non registrando pagine
  create/edit/delete** senza dover azzerare `can*()` come fa `RoleResource` (quello non ha Policy propria):
  `ActivityReportResource` è Policy-backed, il fatto che non esista una pagina "create" la rende sola
  lettura di per sé.

## Report attività pronto E10

Il Mailable/notifica (`ActivityReportPdfGenerated`, US-615) e il resto del catalogo E1-E11 sono documentati
in `app/Domain/Mail/CLAUDE.md` §"Report attività pronto E10" — questo file copre solo lo schema/PDF/comando.
