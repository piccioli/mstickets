<?php

declare(strict_types=1);

namespace App\Import\Security;

use Illuminate\Support\Facades\Hash;

/**
 * Password fissa nota per ogni utente importato con `--anonymize` (US-R08):
 * nomi, email e contenuti restano sempre quelli reali del dump v1 (mai
 * anonimizzati, a differenza del design originale US-217) — l'unica cosa che
 * cambia fuori produzione è la password, mai l'hash v1 reale. Un solo hash
 * noto, comunicato a fine `make setup`/deploy, è sufficiente per il login di
 * collaudo su qualunque utente reale importato.
 */
final class FixedPasswordHasher
{
    private const FIXED_PASSWORD = 'uat';

    /**
     * Non deterministico byte-per-byte (bcrypt sala casualmente ad ogni
     * chiamata): il chiamante deve trattarlo come insert-only, mai
     * confrontarlo in un diff/update (stesso principio già documentato per
     * `released_at`/`done_at` in `TicketsStage`).
     */
    public static function hash(): string
    {
        return Hash::make(self::FIXED_PASSWORD);
    }
}
