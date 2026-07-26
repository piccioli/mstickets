#!/bin/sh
set -eu

# Vedi app-entrypoint.sh: bootstrap/cache/*.php e' escluso da .dockerignore,
# va rigenerato da vendor/composer/installed.json (--no-dev) prima di qualunque
# comando artisan, altrimenti il boot fallisce su provider dev-only stantii.
php artisan package:discover --ansi

restart_loop() {
    while true; do
        "$@" || echo "[worker-entrypoint] processo '$*' terminato, riavvio tra 2s" >&2
        sleep 2
    done
}

restart_loop php artisan queue:work --sleep=3 --tries=3 &
restart_loop php artisan schedule:work &

wait -n
exit 1
