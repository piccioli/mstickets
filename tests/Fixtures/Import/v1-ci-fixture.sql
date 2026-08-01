-- Fixture v1 ridotta per CI (US-218, §14 del PRD).
--
-- Scopo: dataset piccolo (poche righe per tabella) ma rappresentativo, già
-- anonimizzato ALLA CREAZIONE (nessun dato reale di produzione: nomi/email/
-- corpi dei messaggi sono tutti fittizi scritti a mano), usato dal job CI
-- dedicato (.github/workflows/ci.yml, job "etl-fixture") per eseguire
-- `php artisan v1:import` (due volte, per l'idempotenza) e poi
-- `php artisan v1:validate` contro un vero servizio Postgres `db_legacy`,
-- senza richiedere il dump reale di produzione (troppo grande/sensibile per
-- essere versionato, vedi `v1dumps/` in .gitignore).
--
-- Schema: solo le tabelle/colonne v1 effettivamente lette dagli stage
-- registrati in config/import.php (verificate contro
-- v1dumps/production_dump_20260726_101158.sql, non compresso, con
-- `grep -n "CREATE TABLE public.<tabella>"`). Nessun vincolo FK dichiarato
-- qui apposta: alcuni casi limite sotto sono righe orfane per costruzione.
--
-- Casi limite coperti esplicitamente (§ inspect-20260726_101916.md, Fase 0),
-- ognuno con un commento "-- EDGE CASE" sulla riga INSERT corrispondente:
--   1. story.type fuori catalogo ("Epic")
--   2. story.priority fuori catalogo (9)
--   3. users.roles con "editor" (sia da solo che insieme a un altro ruolo)
--   4. customer_request non parsabile (testo semplice, nessun blocco HTML)
--   5. conflitto di gerarchia story_story vs stories.parent_id
--   6. media orfano (file assente su disco) + media con model_type non-Story
--
-- Deliberatamente ESCLUSA da questa fixture: un'email duplicata a meno del
-- case. Il dump reale (inspect-20260726_101916.md) non ne ha mai avuta
-- nessuna, e UsersStage la rileva/segnala ma NON deduplica le righe v2
-- (§UsersStage::duplicateEmailWarnings) — due utenti v2 con la stessa email
-- a meno del case fanno fallire il controllo di unicità di `v1:validate`
-- (V1ValidateCommand::reportUniquenessChecks), con conseguente uscita non a
-- zero. Includerla qui contraddirebbe l'AC di questa stessa story ("il
-- comando esce con successo"). Il caso resta comunque coperto a livello di
-- stage da un test dedicato già esistente:
-- tests/Feature/Import/Stages/UsersStageTest.php
-- ("reports case-insensitive duplicate emails without failing the stage").
--
-- Come rigenerare/estendere questa fixture se lo schema v1 cambia: ripetere
-- il grep sul dump non compresso indicato sopra per le colonne reali di ogni
-- tabella, poi aggiornare sia le CREATE TABLE qui sotto sia (se necessario)
-- gli stage che le leggono. Vedi anche README.md, sezione "CI".

-- =====================================================================
-- Schema
-- =====================================================================

CREATE TABLE users (
    id bigint NOT NULL PRIMARY KEY,
    name varchar(255) NOT NULL,
    email varchar(255) NOT NULL,
    email_verified_at timestamp,
    password varchar(255) NOT NULL,
    remember_token varchar(100),
    roles varchar(255),
    activity_report_language varchar(2) DEFAULT 'it' NOT NULL,
    google_drive_url varchar(255),
    google_drive_budget_url varchar(255),
    created_at timestamp,
    updated_at timestamp
);

CREATE TABLE organizations (
    id bigint NOT NULL PRIMARY KEY,
    name varchar(255) NOT NULL,
    activity_report_language varchar(2) DEFAULT 'it' NOT NULL,
    created_at timestamp,
    updated_at timestamp
);

CREATE TABLE organization_user (
    id bigint NOT NULL PRIMARY KEY,
    organization_id bigint NOT NULL,
    user_id bigint NOT NULL,
    created_at timestamp,
    updated_at timestamp
);

CREATE TABLE documentations (
    id bigint NOT NULL PRIMARY KEY,
    name varchar(255) NOT NULL,
    description text,
    category varchar(255) DEFAULT 'customer' NOT NULL,
    created_at timestamp,
    updated_at timestamp
);

CREATE TABLE tags (
    id bigint NOT NULL PRIMARY KEY,
    name varchar(255) NOT NULL,
    description text,
    estimate numeric(8, 2),
    taggable_id bigint,
    taggable_type varchar(255),
    created_at timestamp,
    updated_at timestamp
);

CREATE TABLE taggables (
    id bigint NOT NULL PRIMARY KEY,
    tag_id bigint NOT NULL,
    taggable_id bigint NOT NULL,
    taggable_type varchar(255) NOT NULL,
    created_at timestamp,
    updated_at timestamp
);

CREATE TABLE stories (
    id bigint NOT NULL PRIMARY KEY,
    name varchar(255) NOT NULL,
    description text,
    status varchar(255) DEFAULT 'new' NOT NULL,
    type varchar(255),
    priority integer DEFAULT 1 NOT NULL,
    user_id bigint,
    creator_id bigint,
    tester_id bigint,
    test_dev varchar(255),
    test_prod varchar(255),
    estimated_hours numeric(5, 2),
    fundraising_project_id bigint,
    waiting_reason text,
    problem_reason text,
    released_at timestamp,
    done_at timestamp,
    parent_id bigint,
    customer_request text,
    created_at timestamp,
    updated_at timestamp
);

CREATE TABLE story_story (
    id bigint NOT NULL PRIMARY KEY,
    parent_id bigint NOT NULL,
    child_id bigint NOT NULL,
    created_at timestamp,
    updated_at timestamp
);

CREATE TABLE story_participants (
    id bigint NOT NULL PRIMARY KEY,
    story_id bigint NOT NULL,
    user_id bigint NOT NULL,
    created_at timestamp,
    updated_at timestamp
);

CREATE TABLE story_logs (
    id bigint NOT NULL PRIMARY KEY,
    story_id bigint NOT NULL,
    user_id bigint,
    viewed_at timestamp NOT NULL,
    changes json,
    created_at timestamp,
    updated_at timestamp
);

CREATE TABLE media (
    id bigint NOT NULL PRIMARY KEY,
    model_type varchar(255) NOT NULL,
    model_id bigint NOT NULL,
    uuid varchar(36),
    name varchar(255) NOT NULL,
    file_name varchar(255) NOT NULL,
    mime_type varchar(255)
);

CREATE TABLE activity_reports (
    id bigint NOT NULL PRIMARY KEY,
    owner_type varchar(255) DEFAULT 'customer' NOT NULL,
    customer_id bigint,
    organization_id bigint,
    report_type varchar(255) DEFAULT 'monthly' NOT NULL,
    year integer NOT NULL,
    month integer,
    created_at timestamp,
    updated_at timestamp
);

CREATE TABLE activity_report_story (
    id bigint NOT NULL PRIMARY KEY,
    activity_report_id bigint NOT NULL,
    story_id bigint NOT NULL,
    created_at timestamp,
    updated_at timestamp
);

CREATE TABLE fundraising_opportunities (
    id bigint NOT NULL PRIMARY KEY,
    name varchar(255) NOT NULL,
    official_url varchar(255),
    endowment_fund numeric(15, 2),
    deadline date NOT NULL,
    program_name varchar(255),
    sponsor varchar(255),
    cofinancing_quota numeric(5, 2),
    max_contribution numeric(15, 2),
    territorial_scope varchar(255) DEFAULT 'national' NOT NULL,
    beneficiary_requirements text,
    lead_requirements text,
    created_by bigint NOT NULL,
    responsible_user_id bigint NOT NULL,
    created_at timestamp,
    updated_at timestamp
);

CREATE TABLE fundraising_projects (
    id bigint NOT NULL PRIMARY KEY,
    title varchar(255) NOT NULL,
    fundraising_opportunity_id bigint NOT NULL,
    lead_user_id bigint,
    created_by bigint NOT NULL,
    responsible_user_id bigint,
    description text,
    status varchar(255) DEFAULT 'draft' NOT NULL,
    requested_amount numeric(15, 2),
    approved_amount numeric(15, 2),
    submission_date date,
    decision_date date,
    created_at timestamp,
    updated_at timestamp
);

CREATE TABLE fundraising_project_partners (
    id bigint NOT NULL PRIMARY KEY,
    fundraising_project_id bigint NOT NULL,
    user_id bigint NOT NULL,
    created_at timestamp,
    updated_at timestamp
);

-- =====================================================================
-- Dati (tutti fittizi, dominio *.test)
-- =====================================================================

INSERT INTO users (id, name, email, password, roles, activity_report_language, created_at, updated_at) VALUES
    (1, 'Mario Rossi', 'mario.rossi@example.test', 'secret', '["developer"]', 'it', '2026-01-01 09:00:00', '2026-01-01 09:00:00'),
    (2, 'Giulia Bianchi', 'giulia.bianchi@example.test', 'secret', '["customer"]', 'it', '2026-01-01 09:00:00', '2026-01-01 09:00:00'),
    (3, 'Luca Verdi', 'luca.verdi@example.test', 'secret', '["fundraising"]', 'it', '2026-01-01 09:00:00', '2026-01-01 09:00:00'),
    (4, 'Anna Neri', 'anna.neri@example.test', 'secret', '["editor"]', 'it', '2026-01-01 09:00:00', '2026-01-01 09:00:00'), -- EDGE CASE 3a: editor come UNICO ruolo (D14: nessun ruolo Spatie assegnato, solo permessi diretti documentation.*, segnalato)
    (5, 'Paolo Gialli', 'paolo.gialli@example.test', 'secret', '["admin","editor"]', 'it', '2026-01-01 09:00:00', '2026-01-01 09:00:00'); -- EDGE CASE 3b: editor insieme a un ruolo riconosciuto (admin assegnato via Spatie + permessi diretti per editor)

INSERT INTO organizations (id, name, activity_report_language, created_at, updated_at) VALUES
    (1, 'ACME Cooperativa Sociale', 'it', '2026-01-01 09:00:00', '2026-01-01 09:00:00');

INSERT INTO organization_user (id, organization_id, user_id, created_at, updated_at) VALUES
    (1, 1, 2, '2026-01-01 09:00:00', '2026-01-01 09:00:00');

INSERT INTO documentations (id, name, description, category, created_at, updated_at) VALUES
    (1, 'Guida operativa', 'Come usare il sistema di ticketing', 'customer', '2026-01-01 09:00:00', '2026-01-01 09:00:00'),
    (2, 'Manuale interno', 'Procedure interne per il team support', 'internal', '2026-01-01 09:00:00', '2026-01-01 09:00:00');

INSERT INTO tags (id, name, description, taggable_id, taggable_type, created_at, updated_at) VALUES
    (1, 'urgente', NULL, NULL, NULL, '2026-01-01 09:00:00', '2026-01-01 09:00:00'),
    (2, 'guida-collegata', NULL, 1, 'App\Models\Documentation', '2026-01-01 09:00:00', '2026-01-01 09:00:00'),
    (3, 'link-non-documentazione', NULL, 999, 'App\Models\Project', '2026-01-01 09:00:00', '2026-01-01 09:00:00'); -- taggable_type diverso da Documentation: collassa a tag semplice, contato nel warning aggregato di TagsStage

INSERT INTO taggables (id, tag_id, taggable_id, taggable_type, created_at, updated_at) VALUES
    (1, 1, 1, 'App\Models\Story', '2026-01-01 09:00:00', '2026-01-01 09:00:00'),
    (2, 1, 1, 'App\Models\Documentation', '2026-01-01 09:00:00', '2026-01-01 09:00:00'); -- lato Documentation della pivot: ignorato da TicketTagsStage (solo lato Story), già assorbito come FK diretta da TagsStage

INSERT INTO stories (id, name, description, status, type, priority, user_id, creator_id, tester_id, parent_id, customer_request, created_at, updated_at) VALUES
    (1, 'Bug login', 'Il login non funziona', 'done', 'bug', 1, 1, 2, 3, NULL,
        'Mario Rossi ha risposto il: 15-01-2026 10:30
 <div style=''background-color: #f5f5f5; border-left: 4px solid #cccccc; padding: 10px 20px;''>Grazie della segnalazione, stiamo verificando il problema di login.</div><div style=''height: 2px; background-color: #e2e8f0; margin: 20px 0;''></div>Buongiorno, il login non funziona piu da ieri sera.',
        '2026-01-01 09:00:00', '2026-01-05 09:00:00'),
    (2, 'Nuova funzionalita reportistica', 'Richiesta di una nuova funzionalita', 'new', 'Epic', 1, 1, 2, NULL, NULL, NULL, '2026-01-02 09:00:00', '2026-01-02 09:00:00'), -- EDGE CASE 1: type fuori catalogo ("Epic") -> fallback helpdesk + warning, mai un crash
    (3, 'Richiesta urgente', 'Priorita fuori scala nel v1', 'new', 'bug', 9, 1, 2, NULL, NULL, NULL, '2026-01-03 09:00:00', '2026-01-03 09:00:00'), -- EDGE CASE 2: priority fuori catalogo (9) -> fallback low + warning
    (4, 'Segnalazione via modulo', 'Segnalazione arrivata da un canale non email', 'new', 'helpdesk', 1, 1, 4, NULL, NULL,
        'Ciao, ho un problema con l''app, potete aiutarmi? Grazie mille.',
        '2026-01-04 09:00:00', '2026-01-04 09:00:00'), -- EDGE CASE 4: customer_request senza il blocco HTML riconosciuto -> fallback a un unico messaggio con l'HTML integrale, nessuna perdita di contenuto
    (5, 'Ticket padre gerarchia', 'Capostipite della gerarchia di test', 'done', 'bug', 1, 1, 2, NULL, NULL, NULL, '2026-01-05 09:00:00', '2026-01-06 09:00:00'),
    (6, 'Ticket figlio gerarchia', 'Figlio con fonti in conflitto', 'new', 'bug', 1, 1, 2, NULL, 5, NULL, '2026-01-06 09:00:00', '2026-01-06 09:00:00'); -- parent_id primario = 5, in conflitto con story_story sotto (EDGE CASE 5)

INSERT INTO story_story (id, parent_id, child_id, created_at, updated_at) VALUES
    (1, 2, 6, '2026-01-06 09:00:00', '2026-01-06 09:00:00'); -- EDGE CASE 5: la pivot dichiara il padre di 6 come la story 2, in conflitto con stories.parent_id=5 sopra: vince parent_id (5), il conflitto viene segnalato, mai un merge silenzioso

INSERT INTO story_participants (id, story_id, user_id, created_at, updated_at) VALUES
    (1, 1, 3, '2026-01-01 09:30:00', '2026-01-01 09:30:00');

INSERT INTO story_logs (id, story_id, user_id, viewed_at, changes, created_at, updated_at) VALUES
    (1, 1, 1, '2026-01-02 10:00:00', '{"status": "progress"}', '2026-01-02 10:00:00', '2026-01-02 10:00:00'),
    (2, 1, 1, '2026-01-05 09:00:00', '{"status": "done"}', '2026-01-05 09:00:00', '2026-01-05 09:00:00'),
    (3, 1, 3, '2026-01-05 11:00:00', '{"watch": "2026-01-05 11:00:00"}', '2026-01-05 11:00:00', '2026-01-05 11:00:00'), -- sola chiave "watch": esclusa da ticket_logs, migra a ticket_views (US-209)
    (4, 1, 3, '2026-01-05 12:00:00', '{"user_id": 1}', '2026-01-05 12:00:00', '2026-01-05 12:00:00'), -- event=assigned
    (5, 1, 2, '2026-01-05 13:00:00', '{"description": true}', '2026-01-05 13:00:00', '2026-01-05 13:00:00'), -- event=updated (diff generico, mai il corpo di description)
    (6, 5, 1, '2026-01-06 09:00:00', '{"status": "done"}', '2026-01-06 09:00:00', '2026-01-06 09:00:00'),
    (7, 6, 1, '2026-01-06 09:30:00', '{"status": "new"}', '2026-01-06 09:30:00', '2026-01-06 09:30:00');

INSERT INTO media (id, model_type, model_id, uuid, name, file_name, mime_type) VALUES
    (1, 'App\Models\Story', 1, '11111111-1111-1111-1111-111111111111', 'foto', 'foto-mancante.png', 'image/png'), -- EDGE CASE 6a: riga presente ma file assente su legacy-media -> segnalato come compromesso, mai importato, mai un crash
    (2, 'App\Models\Documentation', 1, '22222222-2222-2222-2222-222222222222', 'doc', 'doc-mancante.pdf', 'application/pdf'); -- EDGE CASE 6b: model_type diverso da Story -> scartato (contato), il v1 attaccava media anche a entita diverse dalla Story

INSERT INTO activity_reports (id, owner_type, customer_id, organization_id, report_type, year, month, created_at, updated_at) VALUES
    (1, 'customer', 2, NULL, 'monthly', 2026, 1, '2026-02-01 09:00:00', '2026-02-01 09:00:00'),
    (2, 'organization', NULL, 1, 'annual', 2026, NULL, '2026-02-01 09:00:00', '2026-02-01 09:00:00');

INSERT INTO activity_report_story (id, activity_report_id, story_id, created_at, updated_at) VALUES
    (1, 1, 1, '2026-02-01 09:00:00', '2026-02-01 09:00:00'),
    (2, 2, 5, '2026-02-01 09:00:00', '2026-02-01 09:00:00');

INSERT INTO fundraising_opportunities (id, name, deadline, territorial_scope, created_by, responsible_user_id, created_at, updated_at) VALUES
    (1, 'Bando Fondazione Esempio', '2026-12-31', 'national', 1, 3, '2026-01-01 09:00:00', '2026-01-01 09:00:00');

INSERT INTO fundraising_projects (id, title, fundraising_opportunity_id, lead_user_id, created_by, responsible_user_id, status, created_at, updated_at) VALUES
    (1, 'Progetto Alpha', 1, 1, 1, 3, 'draft', '2026-01-02 09:00:00', '2026-01-02 09:00:00');

INSERT INTO fundraising_project_partners (id, fundraising_project_id, user_id, created_at, updated_at) VALUES
    (1, 1, 3, '2026-01-02 09:00:00', '2026-01-02 09:00:00');
