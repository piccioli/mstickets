<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

/**
 * Reads the design tokens (CSS custom properties) from resources/css/theme.css
 * so that PHP-side consumers (e.g. the Filament theme) derive their values from
 * that single source of truth instead of duplicating hex codes by hand.
 */
final class DesignTokens
{
    /**
     * @var array<string, string>|null
     */
    private static ?array $tokens = null;

    public static function get(string $name): string
    {
        $tokens = self::all();

        if (! array_key_exists($name, $tokens)) {
            throw new RuntimeException("Design token [{$name}] not found in resources/css/theme.css.");
        }

        return $tokens[$name];
    }

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        if (self::$tokens !== null) {
            return self::$tokens;
        }

        $path = resource_path('css/theme.css');

        if (! is_file($path)) {
            throw new RuntimeException("Design tokens file not found at [{$path}].");
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException("Unable to read design tokens file at [{$path}].");
        }

        preg_match_all('/--(ms-[a-z0-9-]+)\s*:\s*([^;]+);/i', $contents, $matches, PREG_SET_ORDER);

        $tokens = [];

        foreach ($matches as $match) {
            $tokens[$match[1]] = trim($match[2]);
        }

        return self::$tokens = $tokens;
    }

    /**
     * Returns the primary (first) font family name from a font-stack token,
     * with surrounding quotes stripped (e.g. "'Nunito Sans', sans-serif" -> "Nunito Sans").
     */
    public static function primaryFontFamily(string $name = 'ms-font-sans'): string
    {
        $stack = self::get($name);
        $first = trim(explode(',', $stack)[0]);

        return trim($first, "'\"");
    }

    public static function flushCache(): void
    {
        self::$tokens = null;
    }
}
