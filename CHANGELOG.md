# Changelog

Tutte le modifiche rilevanti a questo progetto sono documentate in questo file.

Il formato è basato su [Keep a Changelog](https://keepachangelog.com/it/1.1.0/),
e il progetto aderisce a [Semantic Versioning](https://semver.org/lang/it/).

## [0.1.0] - 2026-07-26

Prima release: **Fase 0 — Fondazioni** della riscrittura di Orchestrator (PRD-ORCHESTRATOR-V2.md, §14).
Nessuna funzionalità utente in questa release: pone le basi tecniche su cui verrà costruito il Ticketing
core nella Fase 1.

### Aggiunto

- Scaffold del progetto Laravel 13 + Filament 4 con struttura a moduli di dominio (`Domain/`, `Import/`,
  `Filament/`, `Support/`), Pest 4, Pint, Larastan livello 6.
- Stack Docker Compose completo (`app`, `web`, `db`, `redis`, `queue`, `scheduler`, `mailpit`), con
  healthcheck su tutti i servizi, nessun container come root, build-time UID/GID.
- Pipeline CI di base (Pint, Larastan, Pest, build dei container).
- Importazione del design system Montagna Servizi (`docs/design-system.md`, `resources/css/theme.css`) e
  applicazione del tema al pannello Filament.
- `docs/design-inventory.md`: inventario completo di schermate/feature del mockup, classificate in-scope
  o fuori-scope per questa release.
- Servizio `db_legacy` (profilo Compose `etl`), `bin/load-v1-dump` e comando `v1:inspect` per l'ispezione
  del dump v1 prima di finalizzare lo schema.
- Schema di database v2 completo (PRD §5): Identità/Autenticazione, Ticketing, Tag/Documentazione,
  Rendicontazione, Fundraising, Sottosistema email, Infrastruttura di importazione.
- Enum `UserRole`/`Permission` e catalogo permessi completo (PRD §9.3).
- Seeder idempotente ruolo→permessi che materializza la matrice di autorizzazione (PRD §9.4).
- Policy deny-by-default per ogni modello di dominio.
- Gate di accesso al pannello per ruolo e UI minima di gestione ruoli/permessi.
- Comando diagnostico `orchestrator:doctor`.
- Seed di sviluppo con dati realistici su tutti i moduli in scope.
- Target `make setup` per il bootstrap completo dell'ambiente con un solo comando.

### Verificato

- `v1:inspect` eseguito su due dump di produzione reali (19/02/2026 e 26/07/2026): nessuna anomalia
  strutturale nuova, risultati coerenti su un volume dati quasi raddoppiato.
- Suite di qualità verde: Pint, Larastan (livello 6, 0 errori), Pest (257 test).

[0.1.0]: https://github.com/piccioli/mstickets/releases/tag/v0.1.0
