<?php

declare(strict_types=1);

/**
 * Verifica statica del wiring di `cai:import-datapack` (US-803) in
 * `make setup` (locale, best-effort) e nel deploy UAT (`deploy/remote-deploy.sh`
 * + `docker-compose.uat.yml` + `.env.uat.example`). Nessuno di questi file è
 * eseguibile in un test Pest (richiedono Docker/SSH su msuat), quindi la
 * verifica è sul contenuto testuale — stesso principio già usato altrove nel
 * repo per script di shell non altrimenti testabili.
 */
it('runs cai:import-datapack best-effort in make setup, after v1:import', function () {
    $makefile = file_get_contents(base_path('Makefile'));

    $v1ImportPosition = strpos($makefile, 'artisan v1:import --anonymize');
    $caiImportPosition = strpos($makefile, 'artisan cai:import-datapack');

    expect($v1ImportPosition)->not->toBeFalse();
    expect($caiImportPosition)->not->toBeFalse();
    expect($caiImportPosition)->toBeGreaterThan($v1ImportPosition);

    // Best-effort: il comando è dentro un check sull'esistenza del datapack,
    // non chiamato incondizionatamente (a differenza di v1:import).
    expect($makefile)->toContain('cai-datapack/runts-cai.sqlite');
});

it('declares CAI_DATAPACK_HOST_PATH in .env.uat.example, matching bin/push-cai-datapack default remote path', function () {
    $envExample = file_get_contents(base_path('.env.uat.example'));

    expect($envExample)->toContain('CAI_DATAPACK_HOST_PATH=/opt/mstickets-uat/cai-datapack');

    $pushScript = file_get_contents(base_path('bin/push-cai-datapack'));
    expect($pushScript)->toContain('/opt/mstickets-uat/cai-datapack');
});

it('bind-mounts CAI_DATAPACK_HOST_PATH read-only into the app service, same pattern as LEGACY_MEDIA_HOST_PATH', function () {
    $compose = file_get_contents(base_path('docker-compose.uat.yml'));

    expect($compose)->toContain('${LEGACY_MEDIA_HOST_PATH:-/opt/mstickets-uat/v1-media}:/app/storage/app/v1-media');
    expect($compose)->toContain('${CAI_DATAPACK_HOST_PATH:-/opt/mstickets-uat/cai-datapack}:/app/cai-datapack:ro');
});

it('runs cai:import-datapack unconditionally in remote-deploy.sh, after v1:import --anonymize', function () {
    $script = file_get_contents(base_path('deploy/remote-deploy.sh'));

    $v1ImportPosition = strpos($script, 'artisan v1:import --anonymize');
    $caiImportPosition = strpos($script, 'artisan cai:import-datapack');

    expect($v1ImportPosition)->not->toBeFalse();
    expect($caiImportPosition)->not->toBeFalse();
    expect($caiImportPosition)->toBeGreaterThan($v1ImportPosition);

    // Design: remote-deploy.sh è copiato a mano su msuat da un umano dopo il
    // merge (mai sincronizzato in automatico) — il commento in testa al file
    // deve continuare a documentarlo esplicitamente.
    expect($script)->toContain('nessuna automazione lo sincronizza da');
});
