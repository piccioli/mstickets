# Riferimento design — bundle "montagna-servizi-design-system-5cc04e95..."

> Estratto letterale da Claude Design (progetto `b41c13f4-8321-4716-be35-295d0bdd9d1e`, file
> `Login Montagna Servizi.dc.html` + design system importato). Fonte di verità per la fedeltà visiva di
> landing/login/recupero password. Non è il design system del pannello (quello resta
> `docs/design-system.md`/`resources/css/theme.css`, teal/Nunito Sans — invariato).

## Token — colori (`tokens/colors.css`)

```css
--green-900: #0F332B; --green-800: #123F35; --green-700: #164A3F; --green-600: #1D574B; /* BRAND */
--green-500: #2A6E5F; --green-400: #4A8C7C; --green-300: #7FB0A3; --green-200: #B9D4CC;
--green-100: #E3EFEB; --green-50: #F1F7F5;
--larch-700: #A9611E; --larch-600: #C77E2A; --larch-500: #E0963B; --larch-200: #F3D9B4; --larch-100: #F8ECD9;
--stone-900: #1C201E; --stone-800: #2E3532; --stone-700: #454E4A; --stone-600: #5E6863; --stone-500: #7C857F;
--stone-400: #A7AEA9; --stone-300: #CBD1CD; --stone-200: #E4E8E5; --stone-100: #F1F4F2; --stone-50: #F8FAF9;
--white: #FFFFFF;
--success-600: #2A6E5F; --success-100: #E3EFEB; --warning-600: #C77E2A; --warning-100: #F8ECD9;
--danger-600: #C0473B; --danger-100: #F7E6E3; --info-600: #3B7AA0; --info-100: #E4EEF4;

--text-strong: var(--stone-900); --text-body: var(--stone-700); --text-muted: var(--stone-500);
--text-brand: var(--green-600); --text-inverse: var(--white); --text-on-brand: var(--white);
--text-accent: var(--larch-700);
--surface-page: var(--white); --surface-subtle: var(--green-50); --surface-muted: var(--stone-100);
--surface-card: var(--white); --surface-brand: var(--green-600); --surface-brand-strong: var(--green-800);
--surface-inverse: var(--green-900); --surface-accent: var(--larch-100);
--border-subtle: var(--stone-200); --border-default: var(--stone-300); --border-strong: var(--green-600);
--border-inverse: rgba(255,255,255,0.16);
--focus-ring: var(--green-400); --overlay: rgba(15,51,43,0.55);
```

## Token — tipografia (`tokens/typography.css`, `tokens/fonts.css`)

Font: `'Manrope', system-ui, -apple-system, 'Segoe UI', sans-serif` (Google Fonts:
`https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap`).

```css
--fw-regular:400; --fw-medium:500; --fw-semibold:600; --fw-bold:700; --fw-extrabold:800;
--text-xs:0.75rem; --text-sm:0.875rem; --text-base:1rem; --text-md:1.125rem; --text-lg:1.375rem;
--text-xl:1.75rem; --text-2xl:2.25rem; --text-3xl:3rem; --text-4xl:3.75rem; --text-5xl:4.75rem;
--leading-tight:1.08; --leading-snug:1.2; --leading-normal:1.5; --leading-relaxed:1.65;
--tracking-tight:-0.02em; --tracking-snug:-0.01em; --tracking-normal:0; --tracking-wide:0.04em;
--tracking-eyebrow:0.12em;
```

## Token — spaziatura/effetti (`tokens/spacing.css`, `tokens/effects.css`)

```css
--space-1:0.25rem; --space-2:0.5rem; --space-3:0.75rem; --space-4:1rem; --space-5:1.25rem;
--space-6:1.5rem; --space-8:2rem; --space-10:2.5rem; --space-12:3rem; --space-16:4rem; --space-20:5rem;
--space-24:6rem; --space-32:8rem;
--container-max:1200px; --container-narrow:760px;
--gutter: clamp(1.25rem, 4vw, 3rem); --section-y: clamp(3.5rem, 8vw, 7rem);

--radius-xs:6px; --radius-sm:10px; --radius-md:14px; --radius-lg:20px; --radius-xl:28px;
--radius-card:20px; --radius-pill:999px;
--shadow-xs: 0 1px 2px rgba(15,51,43,0.06); --shadow-sm: 0 2px 8px rgba(15,51,43,0.07);
--shadow-md: 0 8px 24px rgba(15,51,43,0.09); --shadow-lg: 0 18px 44px rgba(15,51,43,0.12);
--shadow-focus: 0 0 0 3px rgba(74,140,124,0.45);
--ease-standard: cubic-bezier(0.22, 0.61, 0.36, 1); --ease-out: cubic-bezier(0.16, 1, 0.3, 1);
--dur-fast:140ms; --dur-base:220ms; --dur-slow:360ms;
```

## Componente — Button (`components/forms/Button.jsx`)

Varianti: `primary | secondary | outline | ghost | link`. Taglie: `sm | md | lg`.

- Base: `display:inline-flex` (o `flex` full-width), `align-items:center`, `justify-content:center`,
  `font-weight:var(--fw-bold)`, `letter-spacing:var(--tracking-snug)`, `border-radius:var(--radius-pill)`,
  transizione su transform/background/box-shadow/border-color.
- Size `lg` (usata per tutte le CTA principali del login): `padding:1rem 1.9rem`, `font-size:var(--text-md)`.
- `primary`: `background:var(--surface-brand)`, `color:var(--text-on-brand)`,
  `border:1px solid var(--surface-brand)`, `box-shadow:var(--shadow-xs)`; hover →
  `background:var(--green-700)`, `box-shadow:var(--shadow-sm)`.
- `outline`: `background:transparent`, `color:var(--text-brand)`, `border:1.5px solid var(--border-strong)`;
  hover → `background:var(--green-50)`.
- `link`: nessun background/border/padding, `color:var(--text-brand)`, testo con
  `border-bottom:2px solid currentColor` (sottolineatura "spessa"); hover → `color:var(--green-700)`.
- `disabled`: `opacity:0.5`, `cursor:not-allowed`.
- Attivo (mousedown): `transform:translateY(1px)` (eccetto variante `link`).

## Componente — Input (`components/forms/Input.jsx`)

- Contenitore: `display:flex; flex-direction:column; gap:0.4rem; width:100%`.
- Label: `font-size:var(--text-sm); font-weight:var(--fw-semibold); color:var(--text-strong)`
  (`*` rosso `var(--danger-600)` se `required`).
- Campo: `padding:0.75rem 1rem`, `border-radius:var(--radius-sm)`,
  `border:1.5px solid var(--border-default)` (default) → `var(--green-500)` (focus) →
  `var(--danger-600)` (errore); `box-shadow:var(--shadow-focus)` solo in focus.
- Hint/errore sotto il campo: `font-size:var(--text-sm)`, colore `var(--text-muted)` o
  `var(--danger-600)` se errore.

## Componente — Hero / Navbar / Footer (`components/layout/*.jsx`)

- **Hero**: `min-height:82vh` (di default), immagine full-bleed + gradiente scuro pino
  (`linear-gradient(90deg, rgba(15,51,43,.80) 0%, rgba(15,51,43,.40) 45%, rgba(15,51,43,.10) 100%)` per
  allineamento `left`), eyebrow uppercase verde chiaro, `<h1>` bianco `clamp(2.25rem, 5vw, var(--text-4xl))`,
  sottotitolo bianco 90% opacità, CTA (`Button` `primary`/`secondary` size `lg`).
- **Navbar**: barra sticky, sfondo `rgba(255,255,255,0.92)` + `backdrop-filter: blur(8px)` (variante
  `solid`) o trasparente sopra un hero (variante `transparent`), logo a sinistra, link centrali, CTA a
  destra.
- **Footer**: sfondo `var(--surface-inverse)` (verde molto scuro), testo bianco/bianco attenuato, grid
  con brand/sede legale, navigazione, newsletter (non usata per la landing di questo progetto — vedi
  PRD §4), riga legale in fondo con `border-top` sottile bianco trasparente.

## HTML completo del mockup login (per riferimento — struttura desktop 1440×900 + mobile 390×844 + recupero password 3 step)

Il file originale (`Login Montagna Servizi.dc.html`) usa una sintassi proprietaria del design tool
(`x-import`, `sc-if`, `sc-for`, `{{ }}`) non eseguibile direttamente in Blade: **non va copiato
letteralmente**, va tradotto nella struttura HTML/CSS equivalente descritta sopra, sostituendo:

- `x-import ... Button ...` → `<a>`/`<button>` con le classi/stile Button riportati sopra.
- `x-import ... Input ...` → i campi reali del form Filament (`TextInput` nativo), avvolti nel markup
  del layout con le classi Input riportate sopra.
- `x-import ... Icon name="check"/"mail"/"circle-check" ...` → `<i data-lucide="check|mail|circle-check">`
  (script Lucide da CDN, stesso meccanismo già documentato in `docs/design-system.md` per il pannello).
- Il layout a due colonne (`grid-template-columns:1.05fr 1fr`), il gradiente dell'hero
  (`linear-gradient(155deg, rgba(15,51,43,.88) 0%, rgba(18,63,53,.78) 45%, rgba(29,87,75,.42) 100%)`), i
  3 vantaggi con icona check in cerchio semi-trasparente, il divider "oppure" (**omesso**, §3 del PRD),
  il link di contatto in fondo: vanno riprodotti fedelmente.
- Il flusso di recupero password a 3 step (step 1: form email; step 2: pannello "Controlla la casella"
  con icona mail in cerchio verde chiaro, box informativo, bottoni "Ho ricevuto il link"/"Invia di
  nuovo"; step 3: password + conferma + barra di forza a 3 segmenti + checklist regole + bottone "Salva
  la nuova password", con stato finale "Password aggiornata" e icona circle-check) va riprodotto secondo
  la mappatura a pagine reali Filament descritta nel PRD §6 (step 1+2 = `RequestPasswordReset` con stato
  `$linkSent`, step 3 = `ResetPassword`).

Per il testo esatto delle stringhe italiane del mockup (titoli, sottotitoli, placeholder, messaggi),
fare riferimento a questo estratto conservato in `Login-Montagna-Servizi-source.html` nella stessa
cartella — copia integrale del file originale, usata solo come riferimento testuale/strutturale, non
incluso nella build.
