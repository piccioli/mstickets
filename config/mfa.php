<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Autenticazione a due fattori (§6.7.2 del PRD, US-606)
    |--------------------------------------------------------------------------
    |
    | Ruoli applicativi (App\Domain\Identity\Enums\UserRole) per cui la MFA nativa
    | Filament (autenticazione da app, TOTP) è obbligatoria: un utente con uno di
    | questi ruoli non può usare il pannello finché non la configura. Per ogni
    | altro ruolo resta facoltativa. Nessun ruolo richiesto di default: come le
    | altre feature flag di questo progetto (config/orchestrator.php), abilitarla
    | è una scelta di deploy, mai un default applicativo.
    |
    */

    'required_roles' => array_values(array_filter(explode(',', (string) env('MFA_REQUIRED_ROLES', '')))),

];
