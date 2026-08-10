.PHONY: setup etl-up

# Porta l'ambiente da zero a navigabile con un solo comando (§4.2 del PRD): build,
# avvio dei container, dipendenze PHP, chiave applicativa, db_legacy con l'ultimo
# dump v1 reale (convenzione `v1dumps/latest.sql`, vedi README.md/CLAUDE.md sezione
# ETL) e l'ETL completo via `v1:import --anonymize` — sostituisce il seed fittizio
# precedente (design ETL real data seeding, US-R02): nessun dato fittizio residuo,
# sempre dati reali anonimizzati del dump più recente disponibile. Idempotente:
# rilanciabile senza distruggere dati esistenti (`.env` non viene sovrascritto se
# già presente; il reset di db_legacy, le migrazioni, il seed ruoli e l'ETL sono
# già idempotenti di loro). Gli allegati restano best-effort: se
# `storage/app/v1-media/` è vuota il target non fallisce, l'ETL segnala i media
# come compromesso (comportamento già esistente di `TicketAttachmentsStage`).
setup:
	@test -f .env || cp .env.example .env
	@test -f v1dumps/latest.sql || { \
		echo "Errore: v1dumps/latest.sql non trovato." >&2; \
		echo "Convenzione (README.md/CLAUDE.md, sezione ETL): un umano con accesso SSH a produzione scarica l'ultimo dump e mantiene il puntatore, es.:" >&2; \
		echo "  scp ms:/percorso/dump.sql v1dumps/production_dump_YYYYMMDD_HHMMSS.sql" >&2; \
		echo "  ln -sf production_dump_YYYYMMDD_HHMMSS.sql v1dumps/latest.sql" >&2; \
		exit 1; \
	}
	docker compose build
	docker compose run --rm app sh -c 'for i in 1 2 3; do composer install && exit 0; done; exit 1'
	docker compose run --rm app php artisan key:generate
	npm install
	npm run build
	docker compose up -d
	bin/load-v1-dump v1dumps/latest.sql
	docker compose exec app php artisan migrate --force
	docker compose exec app php artisan db:seed --class=RolePermissionSeeder --force
	docker compose exec app php artisan v1:import --anonymize
	docker compose exec app php artisan collaudo:ensure-manager-account
	@echo ""
	@echo "Setup completato: dati reali importati da v1dumps/latest.sql (--anonymize)."
	@echo "Password di ogni utente (importato o di riferimento): 'password'."
	@echo "Utenti di riferimento del collaudo (docs/collaudo/00-istruzioni-generali.md):"
	@echo "  admin@oc.test | dev@oc.test | fr@oc.test | customer@oc.test | manager@oc.test"

# Avvia il servizio db_legacy (database di appoggio in sola lettura per il dump v1,
# §4.2 / §11.1 P2 del PRD). Non parte con un `docker compose up` normale: richiede
# il profilo Compose dedicato "etl". `make setup` lo avvia già da solo tramite
# `bin/load-v1-dump`; questo target resta utile per gestire il servizio a parte
# (es. prima di `bin/fetch-legacy-media`).
etl-up:
	docker compose --profile etl up -d db_legacy
