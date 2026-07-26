#!/bin/sh
set -eu

# config:cache/event:cache DEVONO girare a runtime, non a build time (Dockerfile):
# env_file inietta le variabili reali (DB_HOST, DB_PASSWORD, APP_KEY, ...) solo
# quando il container viene creato/avviato, non durante `docker build` (che non
# ha accesso a .env.uat, escluso da .dockerignore). config:clear precede la
# nuova cache per non ripartire da una cache stantia lasciata da un'immagine
# precedente nello stesso layer/volume.
php artisan config:clear
php artisan config:cache
php artisan event:cache

exec frankenphp php-server --listen :8000 --root /app/public
