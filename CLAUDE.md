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
