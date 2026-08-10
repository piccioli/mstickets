<?php

declare(strict_types=1);

namespace App\Import\Mappers;

use App\Domain\Identity\Enums\UserRole;

/**
 * Parsing tollerante di `users.roles` (JSON in `varchar`, v1) verso il catalogo
 * `UserRole` di v2 (§11.5 del PRD, stage 2, riusato dallo stage `roles_permissions`).
 * `editor` non è un ruolo v2 (D14): chi lo aveva riceve permessi diretti invece di un
 * ruolo Spatie, gestito dal chiamante tramite il flag `hadEditor`.
 */
final readonly class UserRolesMapper
{
    private const EDITOR_TOKEN = 'editor';

    /**
     * @param  list<UserRole>  $roles  ruoli v2 riconosciuti (mai include "editor")
     * @param  list<string>  $unrecognized  token del JSON v1 che non corrispondono a nessun UserRole né a "editor"
     */
    private function __construct(
        public array $roles,
        public bool $hadEditor,
        public array $unrecognized,
        public bool $parseFailed,
    ) {}

    public static function parse(?string $raw): self
    {
        if ($raw === null || trim($raw) === '') {
            return new self(roles: [], hadEditor: false, unrecognized: [], parseFailed: false);
        }

        $decoded = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            return new self(roles: [], hadEditor: false, unrecognized: [], parseFailed: true);
        }

        $roles = [];
        $unrecognized = [];
        $hadEditor = false;

        foreach ($decoded as $token) {
            if (! is_string($token)) {
                $unrecognized[] = (string) json_encode($token);

                continue;
            }

            $normalized = mb_strtolower(trim($token));

            if ($normalized === self::EDITOR_TOKEN) {
                $hadEditor = true;

                continue;
            }

            $role = UserRole::tryFrom($normalized);

            if ($role === null) {
                $unrecognized[] = $token;

                continue;
            }

            $roles[] = $role;
        }

        return new self(roles: $roles, hadEditor: $hadEditor, unrecognized: $unrecognized, parseFailed: false);
    }
}
