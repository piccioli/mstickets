# Fase 10 — Area Sezioni, Gruppi Regionali e Organi Tecnici

Aperta il 2026-10-21, dopo la chiusura della Fase 9. Riferimento: `PRD-ORCHESTRATOR-V2.md` §14.

## Feature

| # | Area | Feature | PRD | Stato |
|---|---|---|---|---|
| 10.1 | Sezioni | Dashboard V2 | [prd-fase-10-dashboard-sezione-v2.md](prd-fase-10-dashboard-sezione-v2.md) | su UAT (PR #19), verifica del committente in corso |
| 10.2 | GR | Dashboard V2 | [prd-fase-10-dashboard-gr-v2.md](prd-fase-10-dashboard-gr-v2.md) | su UAT (PR #20), verifica del committente in corso |
| 10.3 | GR | Bilancio consolidato Sezioni | [prd-fase-10-bilancio-consolidato-gr.md](prd-fase-10-bilancio-consolidato-gr.md) | su UAT (PR #21), verifica del committente in corso |
| 10.4 | Sezioni | Rifugi di Sezione | [prd-fase-10-rifugi-di-sezione.md](prd-fase-10-rifugi-di-sezione.md) | in sviluppo (run Ralph unico 10.4–10.9) |
| 10.5 | GR | Rifugi delle Sezioni del GR | [prd-fase-10-rifugi-del-gr.md](prd-fase-10-rifugi-del-gr.md) | in sviluppo (run Ralph unico 10.4–10.9) |
| 10.6 | Sezione | Sentieri di Sezione | [prd-fase-10-sentieri-di-sezione.md](prd-fase-10-sentieri-di-sezione.md) | in sviluppo (run Ralph unico 10.4–10.9) |
| 10.7 | GR | Sentieri del GR | [prd-fase-10-sentieri-del-gr.md](prd-fase-10-sentieri-del-gr.md) | in sviluppo (run Ralph unico 10.4–10.9) |
| 10.8 | Sezione | Tappe SICAI | — | non prevista: in osm2cai solo 29 tappe su 525 hanno una Sezione |
| 10.9 | GR | Tappe SICAI | [prd-fase-10-tappe-sicai-gr.md](prd-fase-10-tappe-sicai-gr.md) | in sviluppo (run Ralph unico 10.4–10.9) |
| 10.10 | OTCOSO / Sezioni / GR | Gestione dei Verbali | — | da specificare |
| 10.11 | OTCO / Sezioni / GR | RAG generale | — | da specificare |
| 10.12 | Sezione / GR | Bilancio Sociale (beta) | — | da specificare |

Gli stati sono: da specificare → PRD concordato → in sviluppo → su UAT.

## Processo per ogni feature

1. **PRD concordato a inizio sessione**: il PRD si scrive col committente in
   `docs/project/tasks/prd-fase-10-<slug>.md`, poi si converte in `docs/project/scripts/ralph/prd.json`.
   Nessuna feature parte senza il suo PRD, e non si scrivono PRD di feature future in anticipo.
2. **Una sessione Ralph per feature**, sul branch `ralph/orchestrator-v2-fase-10`. A fine feature
   `prd.json` e `progress.txt` si archiviano in `docs/project/scripts/ralph/archive/<data>-fase-10-<slug>/`
   e `progress.txt` riparte, conservando la sezione "Codebase Patterns".
3. **Dati reali funzionanti**: tutti i dati che si riescono a raccogliere per la feature vanno importati e
   resi funzionanti, non lasciati come esempio. Se non entrano nell'ETL v1 o nei dati CAI/RUNTS già
   presenti, vanno nel datapack (`cai:export-datapack-snapshot` / `cai:import-datapack`).
4. **UAT aggiornato a fine feature**: una PR verso `develop` per feature. Il merge lancia il deploy UAT,
   e prima del merge si spedisce il datapack aggiornato con `bin/push-cai-datapack` (vedi
   `deploy/CLAUDE.md`). La feature è chiusa quando su UAT funziona con i dati del datapack.
5. **Collaudo**: il manifest `docs/collaudo/fase-10.php` cresce a ogni feature (un argomento per feature,
   `collaudo:verify-manifest 10` sempre verde). Il manuale narrativo `docs/collaudo/` della Fase 10 e il
   PDF (`collaudo:generate 10`, nel container `app`) si scrivono per ultimi, a fine fase, come da
   `docs/collaudo/CLAUDE.md`.
