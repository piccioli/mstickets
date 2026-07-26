.PHONY: setup etl-up

# Porta l'ambiente da zero a navigabile con un solo comando (§4.2 del PRD): build,
# avvio dei container, dipendenze PHP, chiave applicativa, migrazioni e seed di
# sviluppo (US-023, 5 utenti/ruolo). Idempotente: rilanciabile senza distruggere
# dati esistenti (`.env` non viene sovrascritto se già presente, le migrazioni e
# il seed di sviluppo sono già idempotenti di loro).
setup:
	@test -f .env || cp .env.example .env
	docker compose build
	docker compose run --rm app sh -c 'for i in 1 2 3; do composer install && exit 0; done; exit 1'
	docker compose run --rm app php artisan key:generate
	npm install
	npm run build
	docker compose up -d
	docker compose exec app php artisan migrate --force
	docker compose exec app php artisan db:seed --force

# Avvia il servizio db_legacy (database di appoggio in sola lettura per il dump v1,
# §4.2 / §11.1 P2 del PRD). Non parte con un `docker compose up` normale: richiede
# il profilo Compose dedicato "etl".
etl-up:
	docker compose --profile etl up -d db_legacy
