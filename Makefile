.PHONY: etl-up

# Avvia il servizio db_legacy (database di appoggio in sola lettura per il dump v1,
# §4.2 / §11.1 P2 del PRD). Non parte con un `docker compose up` normale: richiede
# il profilo Compose dedicato "etl".
etl-up:
	docker compose --profile etl up -d db_legacy
