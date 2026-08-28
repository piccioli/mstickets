#!/bin/bash
# Eseguito su msuat ad ogni push su develop (comando forzato via authorized_keys,
# vedi docs/features/cd-ci-uat-collaudo-v0.2.0/plan.md Task 9 Step 8). Copiato
# manualmente su /root/ticket-uat/deploy/remote-deploy.sh da un umano con accesso
# SSH a msuat quando questo file cambia: nessuna automazione lo sincronizza da
# sola (stessa scelta di "solo un umano tocca msuat" di v1dumps/latest.sql,
# docs/superpowers/specs/2026-08-02-etl-real-data-seeding-design.md §2/§4).
set -euo pipefail
cd /root/ticket-uat/deploy

docker compose -f docker-compose.uat.yml --env-file .env.uat pull

# --wait attende che ogni servizio con healthcheck (incluso db_legacy, sorgente
# dell'ETL) sia "healthy" prima di proseguire, non solo "started" (design ETL
# real data seeding §4: l'import non deve partire prima che db_legacy sia pronto
# a servire connessioni).
docker compose -f docker-compose.uat.yml --env-file .env.uat up -d --wait

# Ogni deploy riparte da zero (design §4: nessun dato persistente tra un push e
# l'altro su develop) importando dati reali via ETL invece di un seed fittizio.
docker compose -f docker-compose.uat.yml --env-file .env.uat exec -T app php artisan migrate:fresh --force
docker compose -f docker-compose.uat.yml --env-file .env.uat exec -T app php artisan db:seed --class=RolePermissionSeeder --force
docker compose -f docker-compose.uat.yml --env-file .env.uat exec -T app php artisan v1:import --anonymize

# Dati CAI/RUNTS (Fase 8): letti dal bind-mount CAI_DATAPACK_HOST_PATH (vedi
# docker-compose.uat.yml), popolato su msuat con bin/push-cai-datapack. A
# differenza di "make setup" (locale, best-effort) qui va SEMPRE eseguito:
# ogni deploy fa migrate:fresh, quindi senza questo passo i dati CAI andrebbero
# persi ad ogni push su develop.
docker compose -f docker-compose.uat.yml --env-file .env.uat exec -T app php artisan cai:import-datapack

# manager@oc.test non esiste in v1 (nessun utente reale ha il ruolo "manager"):
# creato ex novo, non dalla mappa reference_users dell'anonimizzazione.
docker compose -f docker-compose.uat.yml --env-file .env.uat exec -T app php artisan collaudo:ensure-manager-account

docker image prune -f
