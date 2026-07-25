# Design system — Piattaforma Montagna Servizi

Fonte (§8.1 del PRD, import obbligatorio via `claude_design` MCP):
`https://claude.ai/design/p/b41c13f4-8321-4716-be35-295d0bdd9d1e?file=Piattaforma+Montagna+Servizi.dc.html`

File letti: `Piattaforma Montagna Servizi.dc.html` (il mockup funzionale del pannello, screens
dashboard/ticket/nuovo ticket/drive/riunioni/ricerca/bandi/progetti/ETS/escursioni), `support.js`
(runtime del mockup, nessun token di design), `assets/montagna-servizi-mark.png`.

Per §8.3 del PRD, **il design è vincolante** e i valori qui sotto sono estratti dagli stili inline
effettivamente presenti in `Piattaforma Montagna Servizi.dc.html` (non riscritti a mano, non
inventati). Dove il mockup non definisce un valore (es. breakpoint), viene dichiarato esplicitamente
come assente invece di essere inventato.

## Nota importante — due sorgenti distinte trovate nel progetto Claude Design

Il progetto Claude Design importato contiene **due design distinti**:

1. **Il mockup applicativo `Piattaforma Montagna Servizi.dc.html`** (questo file) — usa un verde
   acqua/teal (`#17a180`) e il font **Nunito Sans**. È il mockup funzionale delle schermate del
   pannello (quello richiesto esplicitamente da §8.1) ed è la fonte usata in questo documento.
2. **Un secondo progetto, "Montagna Servizi Design System"** (bundlato in sola lettura sotto
   `_ds/montagna-servizi-design-system-.../` nello stesso progetto Claude Design) — un design
   system del **sito marketing** con palette verde pino (`#1D574B`) e font **Manrope**, con
   componenti, guidelines e token CSS propri.

Le due palette **non coincidono**. Poiché §8.1 richiede esplicitamente l'import del file
`Piattaforma Montagna Servizi.dc.html` e §8.3 dice "il design è vincolante... se il design
definisce colori di stato per i ticket, quelli sostituiscono la palette di §5.3", questo documento
usa il **mockup applicativo** come fonte di verità per il pannello Filament. Il design system del
sito marketing resta disponibile come riferimento per l'identità di brand generale (logo, tono di
voce) ma **non** per i token visivi del pannello. Questa discrepanza va segnalata esplicitamente al
committente al checkpoint di fine Fase 0 (vedi `docs/design-inventory.md`, US-006).

---

## Palette

Nomi semantici assegnati in base all'uso osservato nel mockup (nessun nome semantico era presente
nel sorgente, essendo stili inline).

### Brand / azioni primarie
| Nome semantico | Hex | Uso osservato |
|---|---|---|
| `brand` | `#17a180` | Colore primario: link, bottoni primari, icone di stato attivo, voce di nav selezionata, focus di elementi brandizzati |
| `brand-hover` | `#128a6c` | Hover dei bottoni primari (`background:#17a180` → `#128a6c`) |
| `brand-link-hover` | `#0f7d63` | Hover dei link testuali (`a:hover`) |
| `brand-focus-ring` | `#9fdcc9` | Outline di focus su input/select/textarea (`outline:2px solid #9fdcc9`) |
| `brand-surface-tint` | `#eef7f3` / `#e5f6ef` / `#f1f7f5` | Sfondi tenui verde-menta (badge "NUOVO", box di conferma, suggerimenti ricerca) |
| `brand-border-tint` | `#cfe8de` / `#bfe3d6` / `#d7ebe3` | Bordi sui box a sfondo tenue brand |

### Azioni secondarie (CTA per contesto)
| Nome semantico | Hex | Hover | Uso osservato |
|---|---|---|---|
| `action-danger` | `#e0533f` | — | Asterisco campi obbligatori, badge "Non finanziato" |
| `action-warning-cta` | `#e8622d` | `#d4531f` | Bottone "Crea un nuovo ticket" nella dashboard |
| `action-info-cta` | `#3b6fd4` | `#2e5cba` | Bottone "Vedi tutti i ticket" |
| `action-accent-cta` | `#5b5fc7` | `#4a4eb5` | Bottone "Vai alla documentazione" |
| `success-dot` | `#2ecc71` | — | Indicatore pallino "servizio attivo" |

### Testo
| Nome semantico | Hex | Uso osservato |
|---|---|---|
| `text-strong` | `#3c4557` | Titoli di card, valori principali, testo enfatizzato |
| `text-heading` | `#4a5468` | H1 di pagina (breadcrumb corrente) |
| `text-body` | (eredita `text-strong`) | Corpo, `<body>` di default |
| `text-label` | `#5a6375` | Etichette dei form, voci di nav non selezionate |
| `text-secondary` | `#6b7486` | Testo secondario/didascalie |
| `text-muted` | `#8b93a3` | Placeholder, metadati, testo poco rilevante (colore più usato nel mockup, 91 occorrenze) |
| `text-faint` | `#a8b0bd` / `#b3bac4` | Testo minimo (es. "Precedente/Prossimo" in paginazione) |
| `text-badge` | `#3f4756` | Testo dentro badge di stato colorati |

### Superfici e sfondi
| Nome semantico | Hex | Uso osservato |
|---|---|---|
| `surface-page` | `#f4f6f8` | Sfondo pagina |
| `surface-card` | `#ffffff` | Sfondo card/pannelli |
| `surface-hover-row` | `#fafbfc` | Hover righe tabella/liste |
| `surface-hover-nav` | `#f0f2f5` | Hover voci di navigazione |
| `surface-muted` | `#f1f3f5` / `#f6f7f9` | Sfondi tenui neutri (chip, header tabella) |
| `surface-segmented` | `#e9ecef` | Sfondo dei tab segmentati (pillole di navigazione secondaria) |
| `overlay` | `rgba(30,40,50,.45)` | Overlay modale/drawer |

### Bordi
| Nome semantico | Hex | Uso osservato |
|---|---|---|
| `border-subtle` | `#eceef1` / `#f0f2f5` | Bordo card, separatori |
| `border-default` | `#dfe3e8` | Bordo input/select/textarea |
| `border-muted` | `#e4e7eb` | Separatori secondari |

### Colori di stato ticket/progetto (badge) — parziale, vedi nota
Il mockup definisce esplicitamente coppie `[background, border]` solo per un sottoinsieme delle
etichette usate nelle schermate mostrate, non per tutti i 12 case dell'enum `TicketStatus` (§5.2
del PRD). Testo badge sempre `#3f4756`, `border-radius:99px`, `padding:3px 11px`,
`font-size:11px`, `font-weight:800`.

| Etichetta nel mockup | Background | Border |
|---|---|---|
| NUOVO | `#BFD9F7` | `#8FBCEE` |
| BACKLOG | `#CDD2D8` | `#AEB4BC` |
| TODO | `#FCC79A` | `#F0A263` |
| DA TESTARE | `#F7ECB4` | `#E3D47E` |
| IN ATTESA | `#F2DA8E` | `#DFC15C` |
| COMPLETATO | `#A5E8BA` | `#71D496` |

Etichette equivalenti trovate per il modulo Fundraising (progetti): In valutazione (= BACKLOG),
In progettazione (= TODO), Presentato (= IN ATTESA), Finanziato (= COMPLETATO),
In esecuzione (= NUOVO), In rendicontazione (= DA TESTARE), Concluso (`#7FD6A8`/`#4DBE85`),
Non finanziato (`#F5C2B8`/`#E89B8C`).

**Gap esplicito**: gli stati `assigned`, `progress`, `tested`, `released`, `done`, `problem`,
`rejected` dell'enum `TicketStatus` (§5.2) non hanno un colore dedicato nel mockup. Non sono stati
inventati valori: la story che implementa lo schema Ticketing (US-012) o la UI dei ticket dovrà
scegliere la categoria semantica più vicina fra quelle sopra (es. `progress`→TODO,
`done`/`released`→COMPLETATO, `problem`/`rejected`→danger) e va annotato in
`docs/design-inventory.md`.

## Tipografia

- **Famiglia**: `'Nunito Sans', sans-serif` (caricata da Google Fonts nel mockup:
  `family=Nunito+Sans:opsz,wght@6..12,400;6..12,600;6..12,700;6..12,800`), unica famiglia per tutta
  l'interfaccia (nessuna famiglia display separata).
- **Pesi osservati**: `400` (default body, implicito), `600`, `700` (il più usato per label/valori),
  `800` (titoli di card, badge, eyebrow di sezione).
- **Scala dimensioni osservata** (px, dal mockup): `9, 10.5, 11, 11.5, 12, 12.5, 13, 13.5, 14, 14.5,
  15, 16, 17, 23, 28, 34`. La dimensione più ricorrente è `13.5px` (corpo/label di default, 88
  occorrenze), seguita da `13px` (42) e `12.5px` (27). I valori più grandi (`23px` per H1 di pagina,
  `28px`/`34px` per numeri "hero" nelle card statistiche) sono usati con moderazione.
- **Line-height**: non dichiarato esplicitamente nel mockup salvo casi puntuali (`line-height:1.5`
  su blocchi di testo estesi, `line-height:1.55`–`1.9`–`2`–`2.1` su liste/riepiloghi con più righe
  di metadati). Nessun valore singolo dominante: usare `1.5` come default per il corpo testo e
  `1.2` per titoli, in linea con le convenzioni osservate nei blocchi a riga singola.
- **Letter-spacing**: `0.03em`–`0.05em`–`0.06em`–`0.07em` su eyebrow/etichette maiuscole di
  sezione (nav, intestazioni tabella), nessuno sul corpo testo.

## Spaziature

Il mockup non usa una scala a token dichiarata; i valori di `padding`/`gap` osservati negli stili
inline si concentrano su multipli irregolari di ~2px, con i seguenti valori ricorrenti:
`3, 4, 6, 7, 8, 9, 10, 11, 12, 13, 14, 16, 18, 20, 22, 24, 26, 28, 30` (px). Non c'è un singolo
"base unit" pulito (né 4px né 8px puro): si raccomanda per `resources/css/theme.css` di
arrotondare a una scala 2px-based che copra questi valori (vedi file token) senza inventare nuovi
valori intermedi non osservati.

## Raggi (border-radius)

| Valore | Uso osservato |
|---|---|
| `2px` | Indicatore quadrato piccolo (pallino "servizio attivo") |
| `3px` | — |
| `5px` | Chip toolbar editor testo ("H1", "H2", "H3") |
| `6px` | — |
| `7px` | Bottoni e input (il più usato, 40 occorrenze) |
| `8px` | Card ricerca/tab segmentati |
| `10px` | Card principali (il secondo più usato, 27 occorrenze) |
| `12px` | — |
| `50%` | Avatar/cerchi icona |
| `99px` | Pillole (badge di stato, tab, chip "NUOVO") |

## Ombre

| Nome semantico | Valore | Uso osservato |
|---|---|---|
| `shadow-card` | `0 1px 2px rgba(30,50,40,.05)` | Ombra di default su tutte le card (23 occorrenze, la più diffusa) |
| `shadow-sm` | `0 1px 3px rgba(0,0,0,.08)` | Ombra minima secondaria |
| `shadow-hover-brand` | `0 4px 12px rgba(23,161,128,.10)` | Hover delle card cliccabili (servizi attivi) |
| `shadow-modal` | `0 20px 60px rgba(0,0,0,.25)` | Modale/overlay in primo piano |

## Breakpoint

**Il mockup non definisce alcun breakpoint responsive**: nessuna `@media query` è presente nel
file sorgente (verificato per intero), ed è costruito come prototipo desktop-only a larghezza
fissa (sidebar `232px` fissa + contenuto fluido, card in griglia a 3 colonne fisse). Non viene
inventato un set di breakpoint: il pannello Filament erediterà i breakpoint di default di
Tailwind v4 (`sm/md/lg/xl/2xl`) già usati dal framework, salvo diversa indicazione futura del
committente.

## Stati interattivi

- **Hover**: quasi ogni elemento cliccabile ha uno stile hover esplicito (`style-hover` nel
  mockup) che tipicamente scurisce leggermente il colore di sfondo (bottoni primari) o applica uno
  sfondo tenue neutro/brand (righe, voci di nav, card).
- **Active/press**: i bottoni con azione primaria applicano `transform:translateY(1px)` alla
  pressione (`style-active`), coerente in tutto il mockup — nessuna inversione di colore o
  riduzione di scala.
- **Focus**: `outline:2px solid #9fdcc9; outline-offset:0` su input/select/textarea. Non è definito
  un focus ring per i bottoni nel mockup: il pannello Filament userà il proprio stile di focus di
  default costruito su `--brand-focus-ring`.
- **Disabled**: non osservato nel mockup (nessun controllo disabilitato mostrato nelle schermate
  incluse); da definire secondo i pattern di default di Filament quando necessario.
- **Transizioni**: una sola dichiarazione esplicita trovata, `transition:box-shadow .15s,
  border-color .15s` (sulle card cliccabili). Nessuna curva di easing personalizzata dichiarata:
  usare l'easing di default del browser (`ease`).

## Iconografia

- **Stile**: icone a **singolo tratto (stroke)**, non riempite (`fill="none"`), coerenti con la
  libreria open-source **Lucide** per forma/peso (stessa famiglia di path osservata: cerchi,
  frecce, cartelle, documenti).
- **Spessore tratto (`stroke-width`)**: `1.8` per la maggior parte delle icone di navigazione/UI,
  `2.5` per le icone piccole con maggiore enfasi (frecce di espansione, chevron, check), `2` in
  rari casi.
- **Dimensioni**: perlopiù `11px`–`18px` di lato (icone di navigazione/azione), coerenti con la
  scala testo circostante.
- **Colore**: eredita dal contesto testuale circostante (`currentColor` o hex esplicito coerente
  con `text-secondary`/`text-muted`/`brand` a seconda dello stato).

## File importati e conservati

- `assets/montagna-servizi-mark.png` — mark/logo compatto usato nell'header del mockup (1090×825,
  RGBA con trasparenza).
- `assets/montagna-servizi-logo.png` / `assets/montagna-servizi-logo-white.png` — varianti estese
  del logo, prelevate dal design system di brand collegato allo stesso progetto Claude Design
  (nessuna variante equivalente prodotta autonomamente da questa story).

## Caveat esplicito — nessun formato vettoriale disponibile

L'AC di questa story richiede "SVG per il pannello, PNG per email/PDF". **Nessun file SVG del
logo/mark è presente nel progetto Claude Design importato** (né nel mockup applicativo né nel
design system di brand collegato): sono disponibili solo PNG raster (1600×825 il logo, 1090×825 il
mark). Non è stato ricostruito/tracciato un SVG a partire dal PNG, per non inventare un asset che
il committente non ha fornito. Il pannello Filament (US-005) userà temporaneamente il PNG anche per
il rendering nell'interfaccia; va richiesto un sorgente vettoriale reale al committente prima del
checkpoint di fine Fase 0.
