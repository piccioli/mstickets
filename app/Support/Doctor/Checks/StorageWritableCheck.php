<?php

declare(strict_types=1);

namespace App\Support\Doctor\Checks;

use App\Support\Doctor\Contracts\DoctorCheck;
use App\Support\Doctor\DoctorCheckResult;

/**
 * Verifica i permessi di scrittura sulle directory storage rilevanti in
 * questa fase: i dischi applicativi (`storage/app` e i dischi `private`/
 * `public`) e le directory interne del framework (log, cache, sessioni,
 * viste compilate). Sottocartelle create lazily da comandi specifici (es.
 * `storage/app/import` di US-008) restano responsabilità di quei comandi.
 */
final class StorageWritableCheck implements DoctorCheck
{
    /**
     * @var list<string>
     */
    private const DIRECTORIES = [
        'app',
        'app/private',
        'app/public',
        'framework/cache',
        'framework/sessions',
        'framework/views',
        'logs',
    ];

    public function run(): array
    {
        return array_map(
            static function (string $relativePath): DoctorCheckResult {
                $path = storage_path($relativePath);

                $passed = is_dir($path) && is_writable($path);

                return new DoctorCheckResult(
                    "Scrittura su storage/{$relativePath}",
                    $passed,
                    $passed ? 'scrivibile' : (is_dir($path) ? 'non scrivibile' : 'directory inesistente'),
                );
            },
            self::DIRECTORIES,
        );
    }
}
