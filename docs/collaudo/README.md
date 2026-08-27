# Pacchetto di collaudo UAT — Orchestrator v2

## Cos'è

Questo pacchetto è il manuale di collaudo (User Acceptance Test) di Orchestrator v2 per le
funzionalità realizzate in **Fase 0 (Fondazioni)**, **Fase 1 (Ticketing core)**, **Fase 1A
(Landing, Login, Recupero password)**, **Fase 2 (Importazione dal v1 — ETL)**, **Fase 3
(Sottosistema email)** e **Fase 4 (Tag/commesse, Documentation, Activity Report/Organizations)**:
375 casi di test in totale, organizzati in 75 argomenti, ciascuno tracciato verso un test automatico
realmente esistente nel repository tramite i manifest `fase-0-1.php` (Fase 0/Fase 1), `fase-1a.php`
(Fase 1A), `fase-2.php` (Fase 2), `fase-3.php` (Fase 3) e `fase-4.php` (Fase 4).

Si ispira, nella struttura dei documenti, alla serie di norme ISO/IEC/IEEE 29119 sulla
documentazione di test (senza dichiararne conformità formale): istruzioni generali, specifica dei
casi di test per argomento, registro degli esiti, verbale conclusivo.

## A cosa serve

Guidare un ciclo di collaudo di accettazione ripetibile e tracciabile del software prima del
rilascio, permettendo sia a personale funzionale (senza conoscenza del codice) sia a personale
tecnico di verificare che quanto costruito rispetti i requisiti del PRD e le regole di dominio
descritte in `docs/ticket-lifecycle.md`.

## Chi lo usa

- **Tester funzionale**: esegue i test manuali da interfaccia utente descritti in `02-fase-0.md`,
  `03-fase-1.md`, `04-fase-1a.md`, `05-fase-2.md`, `06-fase-3.md` e `07-fase-4.md`.
- **Tester tecnico/sviluppatore**: esegue i test tecnici (riga di comando, database, suite
  automatica Pest).
- **Product Owner**: approva le classificazioni segnalate come "DA VERIFICARE CON IL PRODUCT
  OWNER" e firma il verbale conclusivo.

## Indice del pacchetto

| File | Contenuto |
|---|---|
| [`00-istruzioni-generali.md`](./00-istruzioni-generali.md) | Scopo, ambito, glossario, ruoli, ambiente UAT, credenziali, criteri di sospensione/superamento, classificazione e procedura di segnalazione delle anomalie. **Da leggere per primo.** |
| [`01-matrice-tracciabilita.md`](./01-matrice-tracciabilita.md) | Matrice che collega ogni test numerato (F0-xx/F1-xx/F1A-xx/F2-xx/F3-xx/F4-xx) al relativo test automatico nel repository, derivata dai manifest `fase-0-1.php`, `fase-1a.php`, `fase-2.php`, `fase-3.php` e `fase-4.php`. |
| [`02-fase-0.md`](./02-fase-0.md) | I 56 test di Fase 0 (Fondazioni), con campi di consuntivazione da compilare durante l'esecuzione. |
| [`03-fase-1.md`](./03-fase-1.md) | I 74 test di Fase 1 (Ticketing core), con campi di consuntivazione da compilare durante l'esecuzione. |
| [`04-fase-1a.md`](./04-fase-1a.md) | I 16 test di Fase 1A (Landing, Login, Recupero password), con campi di consuntivazione da compilare durante l'esecuzione. |
| [`05-fase-2.md`](./05-fase-2.md) | I 74 test di Fase 2 (Importazione dal v1 — ETL), con campi di consuntivazione da compilare durante l'esecuzione. |
| [`06-fase-3.md`](./06-fase-3.md) | I 113 test di Fase 3 (Sottosistema email), con campi di consuntivazione da compilare durante l'esecuzione. |
| [`07-fase-4.md`](./07-fase-4.md) | I 42 test di Fase 4 (Tag/commesse, Documentation, Activity Report/Organizations), con campi di consuntivazione da compilare durante l'esecuzione. |
| [`08-registro-esiti.md`](./08-registro-esiti.md) | Registro aggregato degli esiti di tutti i 375 test e delle anomalie rilevate. |
| [`09-verbale-collaudo.md`](./09-verbale-collaudo.md) | Verbale conclusivo di collaudo, con esito complessivo e firme. |

## Riepilogo numerico

- **375 test totali**
  - 56 test di Fase 0 (F0-01…F0-56), su 9 argomenti
  - 74 test di Fase 1 (F1-01…F1-74), su 14 argomenti
  - 16 test di Fase 1A (F1A-01…F1A-16), su 4 argomenti
  - 74 test di Fase 2 (F2-01…F2-74), su 18 argomenti
  - 113 test di Fase 3 (F3-01…F3-113), su 26 argomenti
  - 42 test di Fase 4 (F4-01…F4-42), su 4 argomenti
- **75 argomenti** in totale (l'elenco completo, con titoli letterali e conteggio test per
  argomento, è nella sezione 3 di `00-istruzioni-generali.md`)

## Come compilare il pacchetto

1. Leggere per intero `00-istruzioni-generali.md` prima di iniziare qualunque test: contiene
   definizioni, credenziali, criteri di sospensione/superamento e la procedura di segnalazione
   delle anomalie richiamati in tutti gli altri file.
2. Eseguire i test in ordine in `02-fase-0.md`, poi in `03-fase-1.md`, poi in `04-fase-1a.md`, poi
   in `05-fase-2.md`, poi in `06-fase-3.md`, poi in `07-fase-4.md`, compilando per ciascun test i
   relativi "Campi di consuntivazione" (esito PASS/FAIL/BLOCKED/NOT APPLICABLE, data, tester,
   evidenze, eventuale anomalia collegata).
3. Al termine di ciascuna fase (o dell'intero ciclo), riportare l'esito aggregato di tutti i 375
   test e l'elenco delle anomalie rilevate in `08-registro-esiti.md`.
4. Chiudere il ciclo di collaudo compilando `09-verbale-collaudo.md`, con l'esito complessivo
   secondo i criteri di superamento del punto 17 di `00-istruzioni-generali.md`.

Prima di avviare o riprendere un ciclo di collaudo, verificare sempre se è avvenuto un nuovo deploy
dell'ambiente UAT: il dataset viene interamente rigenerato ad ogni deploy (sezione 13 delle
istruzioni generali) e i dati creati manualmente durante i test precedenti non sopravvivono.
