# Orchestrator v2 — note per agenti

Repository Laravel 13 + Filament 4 per Montagna Servizi. Spec completa: `../PRD-ORCHESTRATOR-V2.md`.
Piano e story: `../scripts/ralph/prd.json`. Log di avanzamento: `../scripts/ralph/progress.txt`.

## Convenzioni stabilite in Fase 0

- Stack: PHP ^8.4, Laravel 13, Filament 4 (`^4.0`, mai la beta v5).
- Test runner: **Pest 4.x** (non 3.x: `pestphp/pest-plugin-laravel` 3.x non supporta Laravel 13). Test in sintassi Pest (`test('...', function () {...})`), non classi PHPUnit.
- Ogni file PHP scaffoldato inizia con `declare(strict_types=1);` subito dopo `<?php`.
- Struttura a moduli sotto `app/`: `Domain/<Modulo>/{Models,Enums,Actions,...}`, `Import/{Stages,Mappers,Parsers,Validation}`, `Filament/{Resources,Pages,Widgets,Providers}`, `Support/`. Non aggiungere codice di dominio fuori da questa struttura.
- `app/Filament/Providers/AdminPanelProvider.php` (namespace `App\Filament\Providers`) — **non** `app/Providers/Filament/` (quello è il default dell'installer Filament, va spostato).
- Qualità: `composer run lint` (Pint, preset laravel), `composer run analyse` (Larastan livello 6, richiede `--memory-limit=1G` perché il default PHP CLI di 128M non basta), `php artisan test` (Pest).
- Niente business logic negli hook Eloquent (`boot()`/`booted()`/Observer) — vedi PRD §4.4 A1. Ogni mutazione passa da una Action esplicita.
- Enum sempre backed e castati, mai confronti su stringhe grezze (PRD §4.4 A4).
- Pacchetti vietati (non installare mai): `spatie/laravel-google-calendar`, `spatie/laravel-translatable`, `overtrue/laravel-favorite`, `filament-shield`.

## Ambiente locale vs Docker

Lo sviluppo di scaffold è stato verificato con PHP 8.5 locale (compatibile con il vincolo `^8.4`), ma il target Docker/produzione (US-002) è PHP 8.4-FPM: il Dockerfile deve pinnare 8.4, non assumere la versione locale dell'agente.

`docker-compose.yml` ha un `name: orchestrator-v2` esplicito in cima: senza di questo, Compose deriva il nome progetto dalla directory (`orchestrator`), che può collidere con altri stack Docker Compose presenti sulla stessa macchina host. Non rimuovere quel campo. Prima di un `docker compose down`/`rm` verificare sempre `docker ps -a` per non toccare container di altri progetti.

## Design system (US-004/US-005)

- Fonte di verità dei token visivi: `docs/design-system.md` (documento) e `resources/css/theme.css` (custom properties `--ms-*`). Il tema Filament/Tailwind v4 (US-005) **deve** derivare da queste custom properties, non riscrivere hex/valori a mano una seconda volta.
- Il progetto Claude Design (`b41c13f4-8321-4716-be35-295d0bdd9d1e`, tool MCP esposto come `DesignSync`) contiene DUE design distinti bundlati insieme: il mockup applicativo `Piattaforma Montagna Servizi.dc.html` (teal `#17a180`, font Nunito Sans — quello usato per i token) e, sotto `_ds/`, una copia in sola lettura del design system del sito marketing (verde pino `#1D574B`, font Manrope) che **non** è la fonte per il pannello. Non confondere i due se serve ri-consultare il design.
- Nessun SVG del logo/mark è mai stato fornito: solo PNG raster in `assets/`. Se serve un logo vettoriale per il pannello (favicon, sidebar ad alta densità), richiederlo al committente invece di tracciarlo dal PNG.
- I colori di stato ticket nel mockup coprono solo 6 dei 12 case dell'enum `TicketStatus` (§5.2): vedi la tabella di mappatura/gap in `docs/design-system.md` prima di implementare badge di stato in US-012 o nelle risorse Filament dei ticket.
- **Come è cablato il tema Filament (US-005)**: `App\Support\DesignTokens` (in `app/Support/`) legge e parsa `resources/css/theme.css` a runtime (regex su `--ms-*: valore;`) ed espone `DesignTokens::get('ms-brand')`/`DesignTokens::primaryFontFamily()`. `AdminPanelProvider` usa **solo** questa classe per `->colors()`/`->font()` — mai un hex scritto a mano nel Provider. Per aggiungere un nuovo colore al pannello (danger/warning/ecc.), aggiungere prima il token in `theme.css`, poi leggerlo da `DesignTokens::get()`, mai il contrario.
- `->colors([...])` di Filament accetta direttamente una stringa hex (es. `'#17a180'`): internamente chiama `Color::generatePalette()` per costruire la scala 50–950. **Non serve** `Color::hex()`/`Color::rgb()` a meno di dover passare un array di shade già pronto.
- Il tema Vite-compilato del pannello vive in `resources/css/filament/admin/theme.css` (importa `vendor/filament/filament/resources/css/theme.css` + il nostro `resources/css/theme.css`), registrato in `vite.config.js` (`input: [...]`) e in `AdminPanelProvider` via `->viteTheme('resources/css/filament/admin/theme.css')`. Se si aggiungono nuove viste Blade custom sotto `resources/views/filament/`, ricordarsi che le direttive `@source` sono già in quel file — non serve aggiungerle altrove.
- Gli asset di brand (`assets/*.png`) NON sono serviti dal web server da quella posizione: una copia va tenuta in `public/images/branding/` (referenziata con `asset(...)` in `AdminPanelProvider` per `brandLogo`/`darkModeBrandLogo`/`favicon`). `assets/` resta la posizione "sorgente" documentata da US-004; `public/images/branding/` è la copia servibile — se il committente fornisce un SVG in futuro, aggiornare entrambe le posizioni.
- Verifica di temi/branding senza browser MCP disponibile: portare su lo stack con `docker compose up -d db redis app web` (senza `mailpit` se la porta `1025` è occupata da uno stack Docker legacy su questa macchina, vedi nota sopra su `docker ps -a`) e ispezionare l'HTML con `curl` (branding/font/link tema in `<head>`), invece di fermarsi al solo `npm run build`.
