<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Docker

Ambiente di sviluppo containerizzato (§4.2 del PRD): `app` (PHP 8.4-FPM), `web` (nginx, unico entrypoint HTTP), `db` (Postgres 16), `redis` (Redis 7), `queue` (Horizon), `scheduler` (`schedule:work`), `mailpit` (SMTP+IMAP locale).

```bash
cp .env.example .env
docker compose up -d --build
```

L'app è raggiungibile su `http://localhost:8080`, la UI di Mailpit su `http://localhost:8025`. Nessun servizio gira come `root` e non è richiesto alcun `chown` manuale: il mismatch UID/GID tra host e container è risolto in build-time passando `WWWUSER`/`WWWGROUP` (UID/GID dell'utente host) come build arg del servizio `app`.

### Database di appoggio per il dump v1 (`db_legacy`)

Il servizio `db_legacy` (Postgres 16, §4.2 / §11.1 principio P2 del PRD) ospita il dump v1 in **sola
lettura**, isolato dall'esercizio normale: non parte con `docker compose up`, solo col profilo Compose
dedicato `etl`.

```bash
make etl-up                       # avvia (solo) il servizio db_legacy
bin/load-v1-dump path/to/dump.sql # ripristina il dump SQL in db_legacy
```

L'ETL (Fase 2+) non scrive mai sul database v1: `db_legacy` è la sorgente in sola lettura usata da tutto il
codice di importazione successivo (`app/Import/`, comandi `v1:inspect`/`v1:import`/`v1:validate`).

## CI

Ogni pull request esegue `.github/workflows/ci.yml` (GitHub Actions), che deve essere verde prima del merge:

1. **Pint** (`vendor/bin/pint --test`) — stile del codice, preset `laravel`.
2. **Larastan** (`vendor/bin/phpstan analyse --memory-limit=1G`) — analisi statica a livello 6.
3. **Pest con coverage** (`vendor/bin/pest --coverage`) — suite di test (driver di coverage `pcov`).
4. **Build dei container Docker** (`docker compose build app`) — verifica che l'immagine PHP-FPM buildi senza errori.

La pipeline fallisce se uno qualunque di questi step fallisce. Lo step ETL (`php artisan v1:validate`) non esiste ancora in questa fase: il punto di inserimento futuro è commentato direttamente nel workflow, da aggiungere in Fase 2 insieme al comando `v1:import`.

Il badge di stato verrà aggiunto al README non appena il repository avrà un remote GitHub configurato (`https://github.com/<org>/<repo>/actions/workflows/ci.yml/badge.svg`).

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
