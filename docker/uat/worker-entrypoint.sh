#!/bin/sh
set -eu

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
